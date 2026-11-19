<?php

	/*
	 * traffic
	 *
	 * Version 1.0
	 * Copyright 2022
	 *

		//Записать посещение в лог
		$APP->traffic->run();

		//Получить информацию о посетителе (браузер, версия ОС, тип устройства, ip и т.д.)
		$APP->traffic->get_info();

		//Маркировать текущего пользователя (устанавливает куку на год, что бы отличать одного пользователя от другого, при прочих идентичных параметрах)
		$APP->traffic->mark_user();

		//Получить ip адрес клиента
		$APP->traffic->client_ip();

		//Запросить срез логов между указанными датами
		$APP->traffic->shear('2000-01-01', '2500-01-01');

		//Построчно считывать записи лога в срезе.
		$APP->traffic->next();

		//Показать статистику за период, указанный через shear
		$APP->traffic->statistics();

	*/

	namespace App\Facade;

	# ---------------------------------------------------------------- #
	#                  ОПИСАНИЕ     ИНТЕРФЕЙСА                         #
	# ---------------------------------------------------------------- #
	interface QTrafficInterface
	{
		//Записать посещение в лог
		public function push();

		//Получить информацию о посетителе (браузер, версия ОС, тип устройства, ip и т.д.)
		public function get_info();

		// Возвращает объект HTTP-ответа.
		public function mark_user();

		//Получить ip адрес клиента
		public function client_ip();

		//Запросить срез логов между указанными датами
		public function shear($time_begin, $time_end, $keywords=null);

		//Построчно считывать записи лога в срезе.
		public function next();

		//Показать статистику за период, указанный через shear
		public function statistics();
	}


	# ---------------------------------------------------------------- #
	#                 РЕАЛИЗАЦИЯ   ИНТЕРФЕЙСА                          #
	# ---------------------------------------------------------------- #
	class Traffic implements QTrafficInterface
	{
		public $config;

		//Модуль useragent для получения сведений о пользователе
		private $userAgent;
		//Модуль для управления адресной строкой
		private $url;


		/*
		 * name: Переменная хранит врменные параметры для метода sheat
		 * @param files   - хранит выборку всех файлов. попавших под правило метода shear
		 * @param current - Текущий файл из списка shear[files], в котором осуществляется поиск методом next
		 * @param begin   - время начала поиска
		 * @param end     - время окончания среза
		 */
		private $shear = array('files'=>null, 'current'=>null, 'begin'=>null, 'end'=>null);

		//Переменная хранит состояние последнего запроса push.
		//(для отложенной записи в лог и подсчету времени + несколько вызовов push не должно приводить к нескольким записям в журнал)
		private $turn = [];

		public function __construct($userAgent, $url, $config=[])
		{
			$this->userAgent           = $userAgent;
			$this->url                 = $url;

			$this->config['folder']    = sys_get_temp_dir();
			$this->config['marker']    = 'qmark';
			$this->config['unique']    = 'quid';
			$this->config['delimiter'] = '		';

			$config['folder']    = $config['folder']    ? $config['folder']    : sys_get_temp_dir();
			$config['marker']    = $config['marker']    ? $config['marker']    : 'qmark';
			$config['unique']    = $config['unique']    ? $config['unique']    : 'quid';
			$config['delimiter'] = $config['delimiter'] ? $config['delimiter'] : '		';

			//Перезапишем стандартные параметры переданными значениями
			$this->config = array_replace_recursive($this->config, $config);

			//Заменим переменную окружения на реальный путь
			$this->config['folder'] = str_replace('%TEMP%', sys_get_temp_dir().'/', $this->config['folder']);

			//Карта цензуры: [censor] глобал → {путь.точками => 1}
			$this->censor = [];
			foreach ((array) ($this->config['censor'] ?? []) as $var => $node)
			{
				$this->censor[$var] = [];
				$flat = function($n, $p) use(&$flat, $var)
				{
					if (is_array($n)) { foreach ($n as $k=>$v) if (!is_int($k)) $flat($v, $p?$p.'.'.$k:$k); return; }
					if ($p) $this->censor[$var][$p] = 1;				//лист = путь
				};
				$flat($node, '');
			}
			$this->ink = (string) ($this->config['ink'] ?? '▒▒▒▒▒');
			$this->mask = null;	//нет ключей — маски нет, нулевой оверхед

			//uri маскируется по листовым ключам _GET-путей: имя = ключ, либо
			//ключ — скобочный сегмент (user[token], token[], %5B..%5D)
			$keys = array_keys(array_flip(array_map(function($p){ return basename(str_replace('.', '/', $p)); },
			                                  array_keys((array) ($this->censor['_GET'] ?? [])))));
			if ($keys)
			{
				$alt = implode('|', array_map('preg_quote', $keys));
				$b   = '(?:\[[^\]]*\]|%5[bB][^&=]*%5[dD])';					//любая скобка
				$bk  = '(?:\[(?:'.$alt.')\]|%5[bB](?:'.$alt.')%5[dD])';	//скобка с ключом
				$this->mask    = '/([?&;](?:(?:'.$alt.')'.$b.'*|[^&=\s]*'.$bk.$b.'*)=)[^&;\s]*/';
				$this->maskInk = '$1' . str_replace(['\\','$'], ['\\\\','\\$'], $this->ink);
			}

			//Сплющиваем [attach] раз: путь до листа глобала (_SESSION.qlogin)
			$this->attach = [];
			$flat = function($n, $p) use(&$flat)
			{
				if (is_array($n)) { foreach ($n as $k=>$v) $flat($v, $p?$p.'.'.$k:$k); return; }
				if ($n && $p) $this->attach[$p] = 1;
			};
			foreach ((array) ($this->config['attach'] ?? []) as $k=>$v) $flat($v, $k);
		}

		function __destruct()
		{
			if (!$this->turn) return;
			//Если не активно логирование - выходим
			if (! boolval($this->config['enable'])) return false;

			//Добавим время исполнения
			$this->turn['data']['runtime']	= $this->runtime();
			$this->turn['data']['mempeak']	= $this->mempeak();
			$this->turn['data']['code']		= http_response_code();

			//Секция с параметрами дампа суперглобальных пременных.
			//Пути уже сплющены в конструкторе ($this->attach)
			$this->turn['data']['dump'] = [];

			foreach ($this->attach as $var => $_)
			{
				$segs = explode('.', $var);
				$val  = $GLOBALS[array_shift($segs)] ?? null;
				foreach ($segs as $seg)
				{
					if (!is_array($val) or !array_key_exists($seg, $val)) { $val = null; break; }
					$val = $val[$seg];
				}
				if ($val === null) continue;

				//Цензура: пути из [censor] — при захвате глобала целиком
				if (!$segs)
					foreach (array_keys((array) $this->censor[$var]) as $path)
					{
						$ref = &$val; $hit = true;
						foreach (explode('.', $path) as $seg)
							if (is_array($ref) and isset($ref[$seg])) $ref = &$ref[$seg];
							else { $hit = false; break; }
						if ($hit) $ref = $this->ink;
						unset($ref);
					}

				$this->turn['data']['dump'][$var] = $val;
			}

			//Если что-то прилетело в dump - упковываем и фиксируем, если нет - просто указываем пустую строку.
			//PARTIAL_OUTPUT: один битый элемент (utf8/бинарь) → null, а не потеря всего дампа
			$this->turn['data']['dump'] = $this->turn['data']['dump']
				? json_encode($this->turn['data']['dump'], JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR)
				: '';

			//Запишем в лог
			$this->save_file($this->turn['file'], $this->turn['data']);
		}
		/*
		 *
		 * name: Записать посещение в лог
		 * @param (string) advanced string info
		 * @return
		 *
		 */
		public function push($advanced_info = '')
		{
			if (! boolval($this->config['enable'])) return false;

			$info = $this->get_info();
			//Если передеали массив/объект/класс - превратим в json
			$info['info'] = !is_string($advanced_info) ? json_encode($advanced_info) : $this->filterString($advanced_info);

			$filename = $this->date().'.log';

			if (! $this->config['folder'] == '')
			{
				//Если директории нет - создаем
				if (! is_dir($this->config['folder'])) mkdir($this->config['folder'], $this->config['umask'] ?? 0755, true);
				$filename =  $this->config['folder'] .DIRECTORY_SEPARATOR. $filename;
			}

			//Добавим в очередь на запись (мы это запишем при разрушении класса, в самом конце)
			$this->turn['file'] = getcwd().DIRECTORY_SEPARATOR.$filename;
			$this->turn['data'] = $info;
		}

		/*
		 *
		 * name: Получить информацию о посетителе (браузер, версия ОС, тип устройства, ip и т.д.)
		 * @param
		 * @return array info
		 *
		 */
		public function get_info()
		{
			//Создаем статическую переменную, что бы имели возможность кешировать данные при множественных обращениях в рамках одного запроса
			static $return;
			//Если данные были получены до этго - возвращаем
			if (isset($return)) return $return;

			//~ if (!$this->userAgent) return trigger_error ( "Unit UserAgent not found!" , E_USER_ERROR );

			$return['time'] 			= $_SERVER['REQUEST_TIME_FLOAT'];
			$return['code']				= http_response_code();
			$return['runtime'] 			= $this->runtime();
			$return['mempeak'] 			= $this->mempeak();
			$return['ip'] 				= $this->client_ip();
			$return['uri'] 				= $this->mask($_SERVER['REQUEST_URI']);
			$return['type'] 			= $this->userAgent->type;
			$return['osname'] 			= $this->userAgent->osname;
			$return['browsername'] 		= $this->userAgent->browsername;
			$return['browserversion'] 	= $this->userAgent->browserversion;

			$return['page'] 			= $this->url->page();
			$return['unique'] 			= $this->is_unique();
			$return['userid'] 			= $this->mark_user();

			//применим фильтры символов. что бы не поломать лог
			$return['userid'] 			= $this->filterString($return['userid']);
			$return['type'] 			= $this->filterString($return['type']);
			$return['osname'] 			= $this->filterString($return['osname']);
			$return['browsername'] 		= $this->filterString($return['browsername']);
			$return['browserversion'] 	= $this->filterString($return['browserversion']);

			return $return;
		}


		/*
		 *
		 * name: Маркировать текущего пользователя (устанавливает куку на год, что бы отличать одного пользователя от другого, при прочих идентичных параметрах)
		 * @param
		 * @return
		 *
		 */
		public function mark_user()
		{
			//Если пользователь не промаркирован в рамках дня - ставим куку до конца этого дня
			if ($this->is_unique())
			{
				//Ставим актуальную дату посещения (порядковый номер дня в году) и ставим куку до конца этого дня
				$this->set_cookie($this->config['unique'], $this->date(), $_SERVER['REQUEST_TIME']+86400);
			}

			//Если маркер есть, возврщаем
			if ($mark = $this->is_marked()) return $mark;

			//Сгенерируем довольно надежный uid
			$uid = md5(uniqid(random_bytes(16)));

			//Если пользователь не имеет метки - ставим куку, в надежде, что он сохранит её
			$this->set_cookie($this->config['marker'], $uid, $_SERVER['REQUEST_TIME']+31536000);
			//~ setcookie($this->config['marker'], $id, strtotime( '+365 days' ), '/'); //Решение красивое, но  долгое

			return $uid;
		}

		/*
		 *
		 * name: Установить куку с защитными флагами (HttpOnly, Secure, SameSite=Lax)
		 * @param
		 * @return
		 *
		 */
		private function set_cookie($name, $value, $expires)
		{
			$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
				|| (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
				|| (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);

			if (PHP_VERSION_ID >= 70300)
				return setcookie($name, $value, [
					'expires'  => $expires,
					'path'     => $this->url->home(),
					'secure'   => $secure,
					'httponly' => true,
					'samesite' => 'Lax',
				]);

			//PHP < 7.3 не поддерживает массив опций - формируем заголовок вручную
			$header = 'Set-Cookie: '.rawurlencode($name).'='.rawurlencode($value)
				.'; expires='.gmdate('D, d M Y H:i:s T', $expires)
				.'; path='.$this->url->home()
				.'; SameSite=Lax; HttpOnly'
				.($secure ? '; Secure' : '');

			header($header, false);
		}

		/*
		 *
		 * name: Проверить, промаркирован ли текущий пользователь
		 * @param
		 * @return mark id
		 *
		 */
		public function is_marked()
		{
			if (isset($_COOKIE[$this->config['marker']])) return $_COOKIE[$this->config['marker']];
		}

		/*
		 *
		 * name: Проверка на то, посещал ли пользователь в этот день сайт
		 * @param
		 * @return mark id
		 *
		 */
		public function is_unique()
		{
			return ! (isset($_COOKIE[$this->config['unique']]) and ($_COOKIE[$this->config['unique']] == $this->date()));
		}



		private function save_file($logfile, $data)
		{
			$data = implode($this->config['delimiter'], $data);
			return file_put_contents($logfile, "$data\r\n", FILE_APPEND | LOCK_EX );
		}


		/*
		 *
		 * name: Получить ip адрес клиента
		 * @param
		 * @return
		 *
		 */
		public function client_ip()
		{
			//Источники: $_SERVER И getenv() — разные SAPI по-разному
			//наполняют окружение (php -S/php-fpm не прокидывают HTTP_*
			//в env, в CLI наоборот может не быть $_SERVER['REMOTE_ADDR']).
			//X-Forwarded-For может быть списком 'client, proxy1, proxy2' —
			//берём первый (реальный клиент), остальное — прокси
			foreach (['HTTP_CLIENT_IP','HTTP_X_FORWARDED_FOR','HTTP_X_FORWARDED',
				'HTTP_FORWARDED_FOR','HTTP_FORWARDED','REMOTE_ADDR'] as $k)
			{
				$v = $_SERVER[$k] ?? getenv($k) ?: null;
				if (!empty($v))
					return trim(explode(',', $v)[0]);
			}
			return 'UNKNOWN';
		}



		/*
		 *
		 * name: Сделать срез логов по датам
		 * @param $time_begin - время (в формате 0000-00-00 00:00:00 ) с которого начать срез,
		 * @param $time_end - время (в формате 0000-00-00 00:00:00 ) которым закончить срез
		 *
		 * @return
		 *
		 */
		public function shear($time_begin='2000-01-01', $time_end='2500-01-01', $keywords=null)
		{
			//~ if ( (!checkdate($time_begin))	)
			//~ {
				//~ trigger_error ( "TimeBegin ($time_begin) invalid format!" , E_USER_ERROR );
				//~ return $this;
			//~ }
			//~ if ( (!checkdate($time_end))		)
			//~ {
				//~ trigger_error ( "TimeEnd ($time_end) invalid format!" , E_USER_ERROR );
				//~ return $this;
			//~ }

			$unix_begin = strtotime($time_begin);
			$unix_end = strtotime($time_end);

			//Выбираем те файлы логов, в которых хранятся требуемые записи
			$this->shear['files'] = $this->diff_log_files($unix_begin, $unix_end);
			//Заполняем остальные необходимые параметры
			$this->shear['current'] = null;
			$this->shear['begin'] = $time_begin;
			$this->shear['end'] = $time_end;
			$this->shear['keywords'] = $keywords;

			return $this->shear['files'];
		}

		public function next($string=false)
		{
			//Если еще не открывали файлы для анализа
			if ($this->shear['current'] == null)
			{
				//Если файлы для выборки закончились
				if (count($this->shear['files']) == 0) return null;
				//Берем первый лог из списка
				$current_log = array_shift($this->shear['files']);

				//Открываем для чтения
				$this->shear['current'] = fopen($current_log, "r");
			}

			//Если достигнут конец файла...
			if (feof($this->shear['current']))
			{
				//Закрываем его
				fclose($this->shear['current']);
				//Обнуляем указатель на файл
				$this->shear['current'] = null;
				//Еще раз вызываем next, что бы тот переоткрыл следующий файл
				return $this->next($string);
			}

			//Читаем строку из файла
			do
				$buffer = fgets($this->shear['current'], $this->config['bufsize']);
			while ((! feof($this->shear['current'])) and ($buffer == ''));

			//Игнорируем пустые строки
			if ($buffer == '') return $this->next($string);

			//Если попросили вывести строкой (так быстрее в 6 раз)
			if ($string) return $buffer;

			//Возвращаем сформированный результат
			return $this->string_log_parse($buffer);
		}

		/*
		 *
		 * name: Получим статистику
		 * @param
		 * @return
		 *
		 */
		public function statistics($daily=true)
		{
			$result = [];

			foreach ((array) $this->shear['files'] as $filename)
			{
				$result[basename($filename, '.log')] = $this->analyze_file($filename);
			}

			//Если статистика нужна по суткам
			if ($daily)	return $result;

			$sum_stat['page'] = 0;
			$sum_stat['unique'] = 0;

			//Если нужна сумма статистических данных за все время
			foreach ($result as $day => &$day_stat)
			{
				//Подсчитываем одиночные значения
				$sum_stat['page']	+= $day_stat['page'];	unset($day_stat['page']);
				$sum_stat['unique'] += $day_stat['unique'];	unset($day_stat['unique']);

				foreach ($day_stat as $block => &$block_value)
					foreach ($block_value as $key => &$record)
						$sum_stat[$block][$key] += $record;
			}

			return $sum_stat;
		}


		/*
		 *
		 * name: Разбивает строчку логов на параметры
		 * @param
		 * @return
		 *
		 */
		public function string_log_parse($string)
		{
			//Разбираем строчку на параметры
			$buffer = explode($this->config['delimiter'], rtrim($string));

			$return['time'] 			= $buffer[0];
			$return['code'] 			= $buffer[1];
			$return['runtime'] 			= $buffer[2];
			$return['mempeak'] 			= $buffer[3];
			$return['ip'] 				= $buffer[4];
			$return['uri'] 				= $buffer[5];
			$return['type'] 			= $buffer[6];
			$return['osname'] 			= $buffer[7];
			$return['browsername']		= $buffer[8];
			$return['browserversion']	= $buffer[9];

			$return['page'] 			= $buffer[10];
			$return['unique'] 			= $buffer[11];
			$return['userid'] 			= $buffer[12];
			$return['info'] 			= $buffer[13];
			$return['dump'] 			= $buffer[14];
			//~ $return['dump'] 			= $buffer[14] ? json_decode($buffer[14], true) : [];

			return $return;
		}


		/*
		 *
		 * name: Время выполнения скрипта от момента запуска до момента вызова runtime
		 * @param
		 * @return (int) run time seconds
		 *
		 */
		public function runtime()
		{
			return round(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 5);
		}

		/*
		 *
		 * name: Запросить пиковое использование памяти
		 * @param
		 * @return (int) get memory peak
		 *
		 */
		public function mempeak($precision=0)
		{
			return round(memory_get_peak_usage()/1024, $precision);
		}

		/*
		 *
		 * name: Ищет подходящие файлы с логами
		 * @param
		 * @return
		 *
		 */
		private function analyze_file($filename)
		{
			//Если файл не найден
			if (! file_exists($filename)) return null;

			$info = pathinfo($filename);
			if ($info['dirname'] != '') $info['dirname'] .= '/';
			$cache_file = $info['dirname'].$info['filename'].'.info';


			//Если файл с кешем найден
			if (file_exists($cache_file))
			{
				$file = file($cache_file);
				//Если дата модификации файла совпадает - то считаем, что кеш валиден
				if ($file[0] == filemtime($filename)) return unserialize($file[1]);
			}



			//Зачитаем файл логов
			$log = fopen($filename, "r");

			//Запоминаем код вывода ошибок
			$error_reporting = error_reporting();
			error_reporting(E_ALL & ~E_NOTICE);

			$rec['page'] = 0;
			while (! feof($log))
			{
				$buffer = fgets($log, $this->config['bufsize']);
				if ($buffer == '') continue;
				//Распарсим информацию
				$info = $this->string_log_parse($buffer);

				//Заполнчем кеш информацией:
				//Пока пропускаем ботов без анализа
				if ($info['type'] == 'bot')
				{
					$rec['type'][$info['type']]++;
					continue;
				}
				else
				{
					//Увеличиваем счетчик страниц
					$rec['page']++;
					if ($info['unique']) $rec['unique']++;
					$rec['osname'][$info['osname']]++;
					$rec['browsername'][$info['browsername']]++;
					$rec['type'][$info['type']]++;
					$rec['uri'][explode('?', $info['uri'])[0]]++;
				}



			}

			error_reporting($error_reporting);
			fclose($log);

			file_put_contents($cache_file, filemtime($filename)."\n".serialize($rec) );
			return $rec;


		}

		/*
		 *
		 * name: Ищет подходящие файлы с логами
		 * @param
		 * @return
		 *
		 */
		private function diff_log_files($unix_begin, $unix_end)
		{
			$dir = '';
			if (! $this->config['folder'] == '')
				$dir = $this->config['folder'].'/';

			//Получаем список файлов, в которых храняться логи
			$files = glob("$dir*.log");

			$result = array();
			foreach ($files as $key => &$item)
			{
				$current_unix_date = strtotime(basename($item, '.log'));
				if (($unix_begin <= $current_unix_date) and ($current_unix_date <= $unix_end)) $result[] = $item;
			}

			return $result;
		}

		function date()
		{
			static $date;
			if ($date) return $date;

			$date = date('Y-m-d');
			return $date;
		}

		function date1()
		{
			$time = $_SERVER['REQUEST_TIME'];
			$date['Y'] = $time/31536000;
			$date['m'] = '';
		}


		/*
		 *
		 * name: Закрасить значения чувствительных параметров в uri.
		 *       ?a=1&token=abc → ?a=1&token=▒▒▒▒▒   (ключи — [mask], закраска — ink)
		 * @param string
		 * @return string
		 *
		 */
		public function mask($string)
		{
			return $this->mask ? preg_replace($this->mask, $this->maskInk, $string) : $string;
		}


		private function filterString($string)
		{
			return str_replace(["	","\r","\n"], ' ', $string);
		}
	}




	# ---------------------------------------------------------------- #
	# --------------[ СОЗДАЕМ И ПОДКЛЮЧАЕМ ИНТЕРФЕЙС ]---------------- #
	# ---------------------------------------------------------------- #

	//Для забора статистики нужен модуль UserAgent и url. Удовлетворяем зависимость
	return new Traffic($this->useragent, $this->url, $this->config->get());

