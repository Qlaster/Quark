<?php

	/*
	 * Фасад управления службами
	 *
	 * Служба — php-файл в директории service.path (app/services).
	 * Фасад — чистый API: методы возвращают данные, вывод — задача
	 * командной обёртки (engine/console/service/*).
	 *
	 * 	list()                    — службы и их состояние
	 * 	run($name, $args, $flags)   — запуск в текущем процессе (foreground)
	 * 	start($name, $args, $flags) — запуск демоном в фоне
	 * 	stop($name, $pid=null)    — остановка (SIGTERM → SIGKILL)
	 * 	status($name)             — {running, pids[]}
	 * 	output($name, $lines=null)  — вывод процесса службы (лог)
	 *
	 * Разделение $args / $flags — по тому, кому предназначено:
	 *
	 * 	run($name, array $args = [], array $flags = [...])
	 *	       │            │                  │
	 *	       │            │                  └─ УПРАВЛЕНИЕ запуском — наше (фасада)
	 *	       │            │                     force и будущие ключи,
	 *	       │            │                     службе не достаются вообще
	 *	       │            │
	 *	       │            └─ ДАННЫЕ службе — становятся её $argv
	 *	       │               порт, режим, файл — что угодно, её забота
	 *	       │
	 *	       └─ имя файла службы
	 *
	 * В командной строке разделяются префиксом '--':
	 *
	 * 	./console service run ws 8000 debug --force
	 * 	                        │    │      └── flags['force']=true
	 * 	                        │    └──────────┐
	 * 	                        └───────────────┤
	 * 	                               $args = ['8000','debug']
	 * 	                               → служба видит $argv = ['ws','8000','debug']
	 *
	 * Флаги управления ($flags):
	 * 	force — игнорировать уже запущенный инстанс
	 *
	 * Живость определяется по сигнатуре процесса в cmdline
	 * («console service run <name>» или «<title>:<name>») — pid-файлов нет.
	 *
	 * Вывод службы журналируется каналом service.<name> через log-фасад
	 * (таймстампы, дневные файлы, ротация). Маркер в начале строки
	 * «[error] ...» мапится на уровень лога. Если stdout — не терминал
	 * (демон под start), echo глушится и идёт только в журнал.
	 * stderr/fwrite(STDOUT) мимо буфера — сырой файл logs/<name>.log.
	 *
	 */

	namespace App\Facade;


	# ---------------------------------------------------------------- #
	#                  ОПИСАНИЕ     ИНТЕРФЕЙСА                         #
	# ---------------------------------------------------------------- #
	interface QServiceInterface
	{
		/**
		 * Список служб и их состояние.
		 * @return array [name => {name, file, size, mtime, running, pids}]
		 */
		public function list();

		/**
		 * Запуск службы в текущем процессе (foreground).
		 * @param string $name Имя службы (файл app/services/<name>.php).
		 * @param array $args Аргументы, уходящие в $argv службы.
		 * @param array $flags Флаги управления: force — игнорировать уже запущенный инстанс.
		 * @return mixed Результат, который вернул файл службы.
		 */
		public function run($name, array $args = [], array $flags = ['force'=>false]);

		/**
		 * Запуск службы демоном в фоне.
		 * @param string $name Имя службы.
		 * @param array $args Аргументы, уходящие в $argv службы.
		 * @param array $flags Флаги управления: force — игнорировать уже запущенный инстанс.
		 * @return array Результат status() после поднятия.
		 */
		public function start($name, array $args = [], array $flags = ['force'=>false]);

		/**
		 * Остановка службы: SIGTERM → grace-период → SIGKILL.
		 * @param string $name Имя службы.
		 * @param int|null $pid Конкретный pid; null — все процессы службы.
		 * @return array {name, stopped: [pids], running: [pids]}
		 */
		public function stop($name, $pid = null);

		/**
		 * Состояние службы по сигнатуре процесса в cmdline.
		 * @param string $name Имя службы.
		 * @return array {name, running: bool, pids: [int]}
		 */
		public function status($name);

		/**
		 * Вывод службы: журнал канала service.<name> (дневные файлы),
		 * фолбэк — сырой файл редиректа logs/<name>.log.
		 * @param string $name Имя службы.
		 * @param int|null $lines Число последних строк; null — целиком.
		 * @return string|null Содержимое или null если записей нет.
		 */
		public function output($name, $lines = null);
	}


	# ---------------------------------------------------------------- #
	#                 РЕАЛИЗАЦИЯ   ИНТЕРФЕЙСА                          #
	# ---------------------------------------------------------------- #
	class QService implements QServiceInterface
	{

		public $config;
		private $app;
		private $obTail = '';

		function __construct($config, $app = null)
		{
			$this->config = (array) $config;
			$this->app    = $app;
		}


		//~ ------------------------------------------------------------
		//~ Публичное API
		//~ ------------------------------------------------------------

		/**
		 * Список служб и их состояние.
		 * @return array [name => {name, file, size, mtime, running, pids}]
		 */
		public function list()
		{
			//карта процессов собирается один раз на весь список
			$procs  = $this->processes();
			$result = [];

			foreach ((array) glob($this->config['service']['path'].DIRECTORY_SEPARATOR.'*.php') as $file)
			{
				$name = pathinfo($file, PATHINFO_FILENAME);
				$pids = $this->findPids($procs, $name);

				$result[$name] = [
					'name'    => $name,
					'file'    => $file,
					'size'    => filesize($file),
					'mtime'   => filemtime($file),
					'running' => (bool) $pids,
					'pids'    => $pids,
				];
			}

			return $result;
		}

		/**
		 * Запуск службы в текущем процессе (foreground).
		 * @param string $name Имя службы (файл app/services/<name>.php).
		 * @param array $args Аргументы, уходящие в $argv службы.
		 * @param array $flags Флаги управления: force — игнорировать уже запущенный инстанс.
		 * @return mixed Результат, который вернул файл службы.
		 */
		public function run($name, array $args = [], array $flags = ['force'=>false])
		{
			$this->flags($flags);
			$file = $this->file($name);

			//Один инстанс: наша cmdline сама совпадает с сигнатурой — исключаем свой pid
			if (empty($flags['force']) and $pids = array_diff($this->status($name)['pids'], [getmypid()]))
				throw new \Exception("Service '$name' is already running (pid ".implode(',', $pids).")");

			//Сигнатура процесса — видно в ps/proc
			if (function_exists('cli_set_process_title'))
				@cli_set_process_title(($this->config['service']['title'] ?? 'quark:service').":$name");

			//Вывод службы журналируем каналом service.<name> через log-фасад.
			//stdout не терминал (демон под nohup) — глушим его, вывод только в журнал
			$logger  = $this->logger($name);
			$mute    = $logger && function_exists('posix_isatty') && !posix_isatty(STDOUT);
			$obLevel = ob_get_level();
			ob_start(function($chunk) use ($logger, $mute) {
				$this->pushLines($logger, $chunk);
				return $mute ? '' : $chunk;
			}, 4096);

			//Служба видит свой argv как отдельная команда
			if ($this->config['service']['limpid'] ?? true)
			{
				global $argv;
				$argv = array_merge([$name], $args);
			}

			try
			{
				return $this->invoke($file);
			}
			finally
			{
				while (ob_get_level() > $obLevel) ob_end_flush();
				//незавершённая строка без \n — тоже в журнал
				if ($this->obTail !== '') { $this->logLine($logger, $this->obTail); $this->obTail = ''; }
			}
		}

		/**
		 * Запуск службы демоном в фоне.
		 * @param string $name Имя службы.
		 * @param array $args Аргументы, уходящие в $argv службы.
		 * @param array $flags Флаги управления: force — игнорировать уже запущенный инстанс.
		 * @return array Результат status() после поднятия.
		 */
		public function start($name, array $args = [], array $flags = ['force'=>false])
		{
			$this->flags($flags);
			$this->file($name);    //служба существует и читаема, иначе исключение

			//сторожок «один инстанс»: уже работает — отказ; force обходит
			if (empty($flags['force']) and ($status = $this->status($name))['running'])
				throw new \Exception("Service '$name' is already running (pid ".implode(',', $status['pids']).")");

			//сырой лог демона: stdout/stderr мимо журнала (fwrite, fatals)
			$log = $this->config['service']['logs'].DIRECTORY_SEPARATOR.$name.'.log';
			if (!is_dir(dirname($log))) mkdir(dirname($log), 0775, true);

			//демон — новый процесс «console service run <name> [args]»:
			//он сам бутстрапит $APP, ставит сигнатуру и глушит stdout (не tty).
			//Путь к console прибиваем к корню — вызов может быть не из ./console.
			//Бинарь PHP: под вебом PHP_BINARY — это apache/php-fpm, не CLI!
			$php = PHP_SAPI === 'cli' ? PHP_BINARY : PHP_BINDIR.'/php';
			if (!is_executable($php)) $php = trim((string) @shell_exec('command -v php')) ?: PHP_BINARY;

			$cmdline = escapeshellarg($php).' '.escapeshellarg(getcwd().DIRECTORY_SEPARATOR.'console').' service run';
			foreach (array_merge([$name], $args) as $arg) $cmdline .= ' '.escapeshellarg($arg);

			//force передаётся маркером --force — разбирает команда run
			if (!empty($flags['force'])) $cmdline .= ' --force';

			$this->daemonize($cmdline, $log);

			//ждём появления процесса: $timeout секунд, шаг опроса 50мс
			$status = ['running' => false];
			for ($t = 0; !$status['running'] and $t < (int) ($this->config['service']['timeout'] ?? 5); $t += 0.05)
			{
				usleep(50000);
				$status = $this->status($name);
			}

			return $status;
		}

		/**
		 * Остановка службы: SIGTERM → grace-период → SIGKILL.
		 * @param string $name Имя службы.
		 * @param int|null $pid Конкретный pid; null — все процессы службы.
		 * @return array {name, stopped: [pids], running: [pids]}
		 */
		public function stop($name, $pid = null)
		{
			$status  = $this->status($name);
			//один конкретный pid или все процессы службы
			$targets = $pid ? [(int) $pid] : $status['pids'];
			$timeout = (int) ($this->config['service']['timeout'] ?? 5);
			$stopped = [];

			foreach ($targets as $p)
			{
				//SIGTERM — просим завершиться аккуратно
				$this->terminate($p, false);

				//ждём grace-период: демон не наш ребёнок, waitpid не работает —
				//единственный источник истины это /proc/<pid>, опрашиваем каждые 100мс
				$till = time() + $timeout;
				while (time() < $till and $this->alive($p)) usleep(100000);

				//пережил grace — принудительное снятие SIGKILL,
				//запись в таблице процессов исчезает не мгновенно
				if ($this->alive($p))
				{
					$this->terminate($p, true);
					usleep(200000);
				}

				if (!$this->alive($p)) $stopped[] = $p;
			}

			//отчёт: кого сняли, кто остался (пережил даже SIGKILL или чужой pid)
			return [
				'name'    => $name,
				'stopped' => $stopped,
				'running' => array_values(array_diff($status['pids'], $stopped)),
			];
		}

		/**
		 * Состояние службы по сигнатуре процесса в cmdline.
		 * @param string $name Имя службы.
		 * @return array {name, running: bool, pids: [int]}
		 */
		public function status($name)
		{
			$pids = $this->findPids($this->processes(), $name);
			return ['name' => $name, 'running' => (bool) $pids, 'pids' => $pids];
		}

		/**
		 * Вывод службы: журнал канала service.<name> (дневные файлы),
		 * фолбэк — сырой файл редиректа logs/<name>.log.
		 * @param string $name Имя службы.
		 * @param int|null $lines Число последних строк; null — целиком.
		 * @return string|null Содержимое или null если записей нет.
		 */
		public function output($name, $lines = null)
		{
			$name = $this->name($name);

			//Журнал канала service.<name> — дневные файлы log-фасада
			$dir = $this->logDir().DIRECTORY_SEPARATOR.'service.'.$name;
			if ($files = (array) glob($dir.DIRECTORY_SEPARATOR.'*.log'))
			{
				sort($files);
				$out = [];
				for ($i = count($files)-1; $i >= 0 and (!$lines or count($out) < $lines); $i--)
					$out = array_merge(file($files[$i], FILE_IGNORE_NEW_LINES), $out);
				return implode("\n", $lines ? array_slice($out, -$lines) : $out);
			}

			//Фолбэк — сырой файл редиректа демона (stdout/stderr мимо журнала)
			$raw = $this->config['service']['logs'].DIRECTORY_SEPARATOR.$name.'.log';
			if (!is_readable($raw)) return null;
			if (!$lines) return file_get_contents($raw);
			return implode("\n", array_slice(file($raw, FILE_IGNORE_NEW_LINES), -abs($lines)));
		}


		//~ ------------------------------------------------------------
		//~ Платформенный шов
		//~ ------------------------------------------------------------

		//Фоновый запуск процесса: unix — nohup в фон, windows — start /B
		private function daemonize($cmdline, $log)
		{
			if (stripos(PHP_OS, 'WIN') === 0)
				pclose(popen("start /B $cmdline >> ".escapeshellarg($log)." 2>&1", 'r'));
			else
			{
				//Наследуемые дескрипторы родителя гасим (fd>=3): иначе демон
				//удерживает сокет HTTP-запроса и ответ висит до конца службы.
				//Команду передаём через env — escapeshellarg-кавычки не режут
				//обёртку. dash умеет N>&- только до 9 — поэтому bash.
				putenv("QUARK_SPAWN=$cmdline");
				exec("nohup bash -c 'for i in \$(seq 3 255); do eval \"exec \$i>&-\" 2>/dev/null; done; eval exec \"\$QUARK_SPAWN\"' >> ".escapeshellarg($log)." 2>&1 < /dev/null &");
			}
		}

		//Карта процессов [pid => cmdline]: Linux — /proc, Windows — WMI
		private function processes()
		{
			$result = [];

			if (stripos(PHP_OS, 'WIN') === 0)
			{
				exec('powershell -NoProfile -Command "Get-CimInstance Win32_Process | Select-Object ProcessId,CommandLine | ConvertTo-Json -Compress"', $out);
				foreach ((array) json_decode(implode('', $out)) as $p)
					if (!empty($p->CommandLine)) $result[$p->ProcessId] = $p->CommandLine;
			}
			else
				foreach ((array) glob('/proc/[0-9]*/cmdline') as $f)
					$result[(int) basename(dirname($f))] = str_replace("\0", ' ', (string) @file_get_contents($f));

			return $result;
		}

		private function alive($pid)
		{
			if (stripos(PHP_OS, 'WIN') === 0)
			{
				exec('tasklist /FI "PID eq '.(int) $pid.'" /NH 2>&1', $out);
				return strpos(implode(' ', $out), (string) (int) $pid) !== false;
			}
			return file_exists("/proc/".(int) $pid);
		}

		private function terminate($pid, $force)
		{
			if (stripos(PHP_OS, 'WIN') === 0)
				return exec('taskkill '.($force ? '/F ' : '').'/PID '.(int) $pid);
			if (function_exists('posix_kill'))
				return posix_kill($pid, $force ? 9 : 15);
			return exec('kill '.($force ? '-9 ' : '').(int) $pid);
		}


		//~ ------------------------------------------------------------
		//~ Внутреннее
		//~ ------------------------------------------------------------

		//Валидация флагов управления: неизвестный ключ — ошибка, а не тишина
		private function flags(array $flags)
		{
			if ($unknown = array_diff(array_keys($flags), ['force']))
				throw new \Exception("Unknown service flags: ".implode(', ', $unknown));
		}

		//Изолированное подключение службы: внутри файла только $APP и $file —
		//без локальных переменных run() в скоупе
		private function invoke($file)
		{
			$APP = $this->app;
			return require $file;
		}

		//Канал service.<name> в log-фасаде; недоступен — журналируем молча в никуда
		private function logger($name)
		{
			try { return $this->app ? $this->app->log->channel('service.'.$name) : null; }
			catch (\Throwable $e) { return null; }
		}

		//Директория логов log-фасада (для чтения дневных файлов канала)
		private function logDir()
		{
			try { return $this->app->log->config['dir'] ?? 'app/logs'; }
			catch (\Throwable $e) { return 'app/logs'; }
		}

		//Порция вывода → строки в журнал (хвост без \n ждёт следующего чанка)
		private function pushLines($logger, $chunk)
		{
			$this->obTail .= $chunk;
			$lines = explode("\n", $this->obTail);
			$this->obTail = array_pop($lines);
			foreach ($lines as $line) $this->logLine($logger, $line);
		}

		//Строка в журнал; маркер «[error] ...» мапится на уровень
		private function logLine($logger, $line)
		{
			if (!$logger or $line === '') return;
			$level = 'info';
			if (preg_match('~^\[(debug|info|notice|warning|error|critical|alert|emergency)\]\s*(.*)$~i', $line, $m))
				list($level, $line) = [strtolower($m[1]), $m[2]];
			$logger->log($level, $line);
		}

		//Имя службы — строго без путей и спецсимволов
		private function name($name)
		{
			$name = (string) $name;
			if (!preg_match('~^[a-z0-9][a-z0-9_\-.]*$~i', $name))
				throw new \Exception("Bad service name '$name'");
			return $name;
		}

		//Путь к файлу службы
		private function file($name)
		{
			$file = $this->config['service']['path'].DIRECTORY_SEPARATOR.$this->name($name);
			$file = is_file($file.'.php') ? $file.'.php' : $file;

			if (!is_readable($file)) throw new \Exception("Service file not found or no access: $file");
			return $file;
		}

		//Сигнатуры процесса службы: cmdline «console service run <name>» или title
		private function signature($name)
		{
			return [
				'~\bservice\s+run\s+'.preg_quote($name, '~').'\b~',
				'~'.preg_quote(($this->config['service']['title'] ?? 'quark:service').":$name", '~').'~',
			];
		}

		private function findPids(array $procs, $name)
		{
			$pids = [];
			foreach ($procs as $pid => $cmdline)
				foreach ($this->signature($name) as $sign)
					if ($cmdline and preg_match($sign, $cmdline)) { $pids[] = $pid; break; }
			return $pids;
		}

	}



	# ---------------------------------------------------------------- #
	# --------------[ СОЗДАЕМ И ПОДКЛЮЧАЕМ ИНТЕРФЕЙС ]---------------- #
	# ---------------------------------------------------------------- #

	return new QService($this->config->get(__FILE__), $this);
