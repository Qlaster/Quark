<?php

	/*
	 * Обновление платформы из дистрибутива.
	 *
	 * 	./console update [источник] [--dry] [--no-backup] [--no-test]
	 *
	 * 	источник    — папка дистрибутива или URL zip-архива;
	 * 	              по умолчанию — update.ini[update.distr.zip]
	 * 	--dry       — только план и отчёт, ничего не трогает
	 * 	--no-backup — не делать точечный бэкап перед apply
	 * 	--no-test   — пропустить ./console test в стейджинге
	 *
	 * Пайплайн:
	 * 	1. fetch    — дистрибутив в /tmp/quark-update-<ts>/pkg
	 * 	2. plan     — файлы пакета → ignore|merge|db|replace|new
	 * 	3. stage    — сборка «нового релиза под твои данные» в /tmp:
	 * 	              merge-файлы слиты с продом, базы — копии живых
	 * 	              с накатанной схемой пакета
	 * 	4. test     — ./console test в стейдже (ворота; нет файла → skip)
	 * 	5. maintain — maintenance=1 в .env (сайт в 503, error/maintenance)
	 * 	6. backup   — точечный слепок затрагиваемых файлов → backups/update-<ts>/
	 * 	7. apply    — staging наливается поверх прода (copy→tmp→rename)
	 * 	8. release  — maintenance=0, сайт живой
	 *
	 * Провал до фазы 5 — прод не тронут, staging в /tmp сотрётся ребутом.
	 * Провал в apply — restore из точечного бэкапа → flag off → error.
	 */

	$cfg = $APP->config->get(__DIR__.'/update.ini');
	$up  = (array) ($cfg['update'] ?? []);

	$dry      = !empty($flags['dry']);
	$noBackup = !empty($flags['no-backup']);
	$noTest   = !empty($flags['no-test']);

	$log = $APP->log->channel('update');
	$say = function ($m) { echo $m."\n"; };


	# ---------------------------------------------------------------- #
	# 0. Источник: папка | URL zip | дефолт из конфига                 #
	# ---------------------------------------------------------------- #

	$source  = $args[0] ?? ($up['distr']['zip'] ?? null);
	if (!$source) throw new Exception('Источник обновления не указан (аргумент или update.ini[update.distr.zip])');

	$staging = sys_get_temp_dir().'/quark-update-'.date('Ymd_His');
	if (!mkdir($staging, 0775, true)) throw new Exception("Directory '$staging' creation denied");

	if (is_dir($source))
	{
		$distro = realpath($source);
		$say("Источник: $distro");
	}
	else
	{
		$say("Скачиваем: $source");
		$zipfile = "$staging/distro.zip";
		if (!@copy($source, $zipfile)) throw new Exception("Failed to download '$source'");

		$za = new ZipArchive();
		if ($za->open($zipfile) !== true) throw new Exception("Failed to open '$zipfile'");
		$za->extractTo("$staging/pkg");
		$za->close();

		//github-архив заворачивает всё в один корневой каталог (Quark-main/) — спускаемся
		$distro = "$staging/pkg";
		$inside = glob("$distro/*") ?: [];
		if (count($inside) === 1 and is_dir($inside[0])) $distro = $inside[0];
		$say("Распаковано в $distro");
	}

	//маркеры платформы — и в пакете, и в цели
	foreach ((array) ($up['rootmarker'] ?? []) as $m)
	{
		if (!file_exists("$distro/$m"))  throw new Exception("Package '$distro' does not contain platform (no $m)");
		if (!file_exists(getcwd()."/$m")) throw new Exception("Target is not a platform root (no $m)");
	}


	# ---------------------------------------------------------------- #
	# 1. План: классификация файлов пакета                             #
	# ---------------------------------------------------------------- #

	$masks = function ($masks, $rel) {
		foreach ((array) $masks as $mask) if (fnmatch($mask, $rel)) return true;
		return false;
	};
	$isDb = function ($rel) {
		return in_array(strtolower(pathinfo($rel, PATHINFO_EXTENSION)), ['sqlite','db','dba']);
	};

	$plan = [];
	$rii  = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator($distro, FilesystemIterator::SKIP_DOTS),
		RecursiveIteratorIterator::SELF_FIRST);
	foreach ($rii as $f)
	{
		if (!$f->isFile()) continue;
		$rel = substr($f->getPathname(), strlen($distro)+1);

		if     ($isDb($rel))                          $class = 'db';
		elseif ($masks($up['ignore'] ?? [], $rel))    $class = 'ignore';
		elseif ($masks($up['merge']  ?? [], $rel) and file_exists($rel)) $class = 'merge';
		elseif (file_exists($rel))                    $class = 'replace';
		else                                          $class = 'new';

		if ($class !== 'ignore')
			$plan[$rel] = ['class'=>$class, 'src'=>$f->getPathname(), 'staged'=>"$staging/$rel"];
	}
	if (!$plan) throw new Exception('Пустой план — в пакете нечего применять');


	# ---------------------------------------------------------------- #
	# 2/3. Стейджинг: merge-файлы + базы + чистые файлы                #
	# ---------------------------------------------------------------- #

	$stageDir = function ($path) {   //проложить каталог для staged-файла
		$d = dirname($path);
		if (!is_dir($d) and !mkdir($d, 0775, true)) throw new Exception("Directory '$d' creation denied");
	};

	foreach ($plan as $rel => $p)
	{
		$stageDir($p['staged']);

		if ($p['class'] === 'merge')
		{
			//тройной replace: база=твои значения, +новые ключи, твои выигрывают.
			//Читаем/пишем напрямую: config->get/set навязывает .ini-суффикс,
			//env-файлы без расширения ему недоступны
			$read = function ($f) { return is_file($f) ? (array) parse_ini_file($f, true) : []; };
			$merged = array_replace_recursive($read($rel), $read($p['src']), $read($rel));
			iniWrite($merged, $p['staged']);
		}
		elseif ($p['class'] === 'db')
		{
			//живую базу копируем в стейдж и накатываем схему пакета
			if (!copy($rel, $p['staged'])) throw new Exception("Failed to copy '$rel' to staging");
			dbSync($p['src'], $p['staged'], $rel, $log);
		}
		else
			if (!copy($p['src'], $p['staged'])) throw new Exception("Failed to stage '$rel'");
	}


	# ---------------------------------------------------------------- #
	# Отчёт по плану                                                   #
	# ---------------------------------------------------------------- #

	$stat = ['merge'=>0,'db'=>0,'replace'=>0,'new'=>0];
	foreach ($plan as $p) $stat[$p['class']]++;
	$say("План: replace={$stat['replace']} merge={$stat['merge']} db={$stat['db']} new={$stat['new']}");

	if ($dry)
	{
		foreach ($plan as $rel => $p) $say("  [{$p['class']}] $rel");
		$say("--dry: стейджинг собран в $staging, прод не тронут");
		return;
	}

	$log->info("update started from $source (staging $staging)");


	# ---------------------------------------------------------------- #
	# 4. Тесты в стейдже — ворота                                      #
	# ---------------------------------------------------------------- #

	if (!$noTest and is_file("$staging/engine/console/test.php"))
	{
		$say('Тесты стейджинга...');
		$php = PHP_SAPI === 'cli' ? PHP_BINARY : (PHP_BINDIR.'/php');
		exec('cd '.escapeshellarg($staging).' && '.escapeshellarg($php).' console test 2>&1', $out, $code);
		if ($code !== 0)
		{
			$log->error("update aborted: staging tests failed (code $code): ".implode(' | ', $out));
			throw new Exception("Тесты стейджинга провалены (code $code):\n".implode("\n", $out));
		}
		$say('Тесты пройдены');
	}
	else $say('Тесты пропущены');


	# ---------------------------------------------------------------- #
	# 5. Обслуживание + точечный бэкап                                 #
	# ---------------------------------------------------------------- #

	$this->dispatch(['maintenance', 'on']);
	$log->info('maintenance on');

	$backup = 'backups/update-'.date('Ymd_His');
	if (!$noBackup)
	{
		foreach ($plan as $rel => $p)
		{
			//new-классу бэкапить нечего — файла в проде ещё нет
			if ($p['class'] === 'new' or !file_exists($rel)) continue;
			$dst = "$backup/$rel";
			$d = dirname($dst);
			if (!is_dir($d)) mkdir($d, 0775, true);
			copy($rel, $dst);
		}
		$say("Бэкап затронутых файлов: $backup");
		$log->info("backup: $backup");
	}


	# ---------------------------------------------------------------- #
	# 6. Apply: из глубины наружу (engine/core → корень последним)     #
	# ---------------------------------------------------------------- #

	$sorted = array_keys($plan);
	usort($sorted, function ($a, $b) {
		return substr_count($b, '/') <=> substr_count($a, '/');   //глубже — раньше
	});

	$applied = [];
	try
	{
		foreach ($sorted as $rel)
		{
			$tmp = "$rel.new";
			if (!copy($plan[$rel]['staged'], $tmp)) throw new Exception("Failed to copy staged '$rel'");
			if (file_exists($rel) and !rename($rel, "$rel.old")) throw new Exception("Failed to aside '$rel'");
			if (!rename($tmp, $rel)) throw new Exception("Failed to place '$rel'");
			if (file_exists("$rel.old")) unlink("$rel.old");
			$applied[] = $rel;
		}
	}
	catch (Throwable $e)
	{
		//откат применённого из бэкапа, флаг снимаем в любом случае
		foreach ($applied as $rel)
			if (file_exists("$backup/$rel")) { @copy("$backup/$rel", "$rel.new"); @rename("$rel.new", $rel); }
		$this->dispatch(['maintenance', 'off']);
		$log->error("update failed on '$rel', rolled back: ".$e->getMessage());
		throw $e;
	}


	# ---------------------------------------------------------------- #
	# 7. Release + отчёт                                               #
	# ---------------------------------------------------------------- #

	$this->dispatch(['maintenance', 'off']);
	$log->info("update applied: ".json_encode($stat, JSON_UNESCAPED_UNICODE));

	$say("Готово: replace={$stat['replace']} merge={$stat['merge']} db={$stat['db']} new={$stat['new']}");
	$say("Бэкап: $backup  |  Стейджинг: $staging (в /tmp — самоочистится)");


	# ---------------------------------------------------------------- #
	#   ini-писатель для merge-файлов: секции + ключи.                 #
	#   Комментарии и форматирование не переживают (принято).          #
	# ---------------------------------------------------------------- #
	function iniWrite($cfg, $file)
	{
		$val = function ($v) {
			if (is_array($v)) return '"'.json_encode($v, JSON_UNESCAPED_UNICODE).'"';
			if (is_bool($v))  return $v ? '1' : '0';
			return is_numeric($v) ? $v : '"'.addcslashes($v, '"\\').'"';
		};

		$out = '';
		foreach ($cfg as $sec => $kv)
		{
			if (!is_array($kv)) { $out .= "$sec = ".$val($kv)."\n"; continue; }   //скаляр вне секций
			$out .= "[$sec]\n";
			foreach ($kv as $k => $v) $out .= "$k = ".$val($v)."\n";
			$out .= "\n";
		}
		if (file_put_contents($file, $out, LOCK_EX) === false) throw new Exception("Failed to write merged '$file'");
	}

	# ---------------------------------------------------------------- #
	#   Схемо-синхронизация sqlite-базы: референс пакета → живая       #
	#   Аддитивно: недостающие таблицы/колонки добавляются,             #
	#   конфликт типов — скип с warning. Данные не трогаем.            #
	# ---------------------------------------------------------------- #
	function dbSync($refFile, $liveCopy, $rel, $log)
	{
		$ref  = new PDO('sqlite:'.$refFile);
		$live = new PDO('sqlite:'.$liveCopy);
		$ref->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$live->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

		$tables = function ($db) {
			return $db->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
		};
		$cols = function ($db, $table) {
			$out = [];
			foreach ($db->query("PRAGMA table_info(".SQLite3::escapeString($table).")") as $c)
				$out[$c['name']] = $c;
			return $out;
		};

		foreach ($tables($ref) as $table)
		{
			if (!in_array($table, $tables($live)))
			{
				//таблица есть в пакете, нет в живой — создаём по DDL пакета
				$ddl = $ref->query("SELECT sql FROM sqlite_master WHERE name=".SQLite3::escapeString("'$table'"))->fetchColumn();
				$live->exec($ddl);
				$log->info("migrate $rel: +table $table");
				continue;
			}

			$lc = $cols($live, $table);
			foreach ($cols($ref, $table) as $name => $c)
			{
				if (!isset($lc[$name]))
				{
					$type = $c['type'] ?: 'TEXT';
					$live->exec("ALTER TABLE ".SQLite3::escapeString($table)." ADD COLUMN ".SQLite3::escapeString($name)." $type");
					$log->info("migrate $rel: $table +column $name $type");
				}
				elseif (strtolower($lc[$name]['type']) !== strtolower($c['type']))
					$log->warning("migrate $rel: $table.$name type differs ({$lc[$name]['type']} vs {$c['type']}) — skipped");
			}
		}
	}
