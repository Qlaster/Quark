<?php

	/*
	 * visits
	 *
	 * Version 1.0
	 * Copyright 2022
	 *

		//Записать посещение в лог
		$APP->visits->run();

		//Получить информацию о посетителе (браузер, версия ОС, тип устройства, ip и т.д.)
		$APP->visits->get_info();

		//Маркировать текущего пользователя (устанавливает куку на год, что бы отличать одного пользователя от другого, при прочих идентичных параметрах)
		$APP->visits->mark_user();

		//Получить ip адрес клиента
		$APP->visits->client_ip();

		//Запросить срез логов между указанными датами
		$APP->visits->shear('2000-01-01', '2500-01-01');

		//Построчно считывать записи лога в срезе.
		$APP->visits->next();

		//Показать статистику за период, указанный через shear
		$APP->visits->statistics();

	*/

	namespace unit\visits;

	# ---------------------------------------------------------------- #
	#                  ОПИСАНИЕ     ИНТЕРФЕЙСА                         #
	# ---------------------------------------------------------------- #
	interface QVisitInterface
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
	class Visits implements QVisitInterface
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
		}

		function __destruct()
		{
			if (!$this->turn) return;
			//Добавим время исполнения
			$this->turn['data']['runtime']	= $this->runtime();
			$this->turn['data']['mempeak']	= $this->mempeak();
			$this->turn['data']['code']		= http_response_code();

			//Секция с параметрами дампа суперглобальных пременных
			foreach ((array) $this->config['dump'] as $var => $_enable)
				if ($_enable && $GLOBALS[$var]) $this->turn['data']['dump'][$var] = $GLOBALS[$var];

			//Если что-то прилетело в dump - упковываем и фиксируем, если нет - просто указываем пустую строку
			$this->turn['data']['dump'] = $this->turn['data']['dump'] ? json_encode($this->turn['data']['dump']) : '';

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
			$return['uri'] 				= $_SERVER['REQUEST_URI'];
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
				//~ $date = getdate();
				//~ $date = array_combine(['year', 'mon', 'mday'], explode('-', $this->date())); //Так получается быстрее, чем строка выше
				//~ $lifetime = mktime(0,0,0, $date['mon'], $date['mday']+1, $date['year']);
				//Ставим актуальную дату посещения (порядковый номер дня в году) и ставим куку до конца этого дня
				setcookie($this->config['unique'], $this->date(), $_SERVER['REQUEST_TIME']+86400, $this->url->home());
			}

			//Если маркер есть, возврщаем
			if ($mark = $this->is_marked()) return $mark;

			//Сгенерируем довольно надежный uid
			$uid = md5(uniqid(random_bytes(16)));

			//Если пользователь не имеет метки - ставим куку, в надежде, что он сохранит её
			setcookie($this->config['marker'], $uid, $_SERVER['REQUEST_TIME']+31536000, $this->url->home());
			//~ setcookie($this->config['marker'], $id, strtotime( '+365 days' ), '/'); //Решение красивое, но  долгое

			return $uid;
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
			//~ return ! (isset($_COOKIE[$this->config['unique']]));

			//~ if (isset($_COOKIE[$this->config['unique']]) and ($_COOKIE[$this->config['unique']] == $this->date())) return false;
			//~ return true;
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
			$ipaddress = '';
			if (getenv('HTTP_CLIENT_IP'))
				$ipaddress = getenv('HTTP_CLIENT_IP');
			else if(getenv('HTTP_X_FORWARDED_FOR'))
				$ipaddress = getenv('HTTP_X_FORWARDED_FOR');
			else if(getenv('HTTP_X_FORWARDED'))
				$ipaddress = getenv('HTTP_X_FORWARDED');
			else if(getenv('HTTP_FORWARDED_FOR'))
				$ipaddress = getenv('HTTP_FORWARDED_FOR');
			else if(getenv('HTTP_FORWARDED'))
			   $ipaddress = getenv('HTTP_FORWARDED');
			else if(getenv('REMOTE_ADDR'))
				$ipaddress = getenv('REMOTE_ADDR');
			else
				$ipaddress = 'UNKNOWN';
			return $ipaddress;
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
				return $this->next();
			}

			//Читаем строку из файла
			do
				$buffer = fgets($this->shear['current'], $this->config['bufsize']);
			while ((! feof($this->shear['current'])) and ($buffer == ''));

			//Игнорируем пустые строки
			if ($buffer == '') return $this->next();

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
				//Проверяем его валидность
				//~ return unserialize(file_get_contents($cache_file));
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


		private function filterString($string)
		{
			return str_replace(["	","\r","\n"], ' ', $string);
		}
	}




	# ---------------------------------------------------------------- #
	# --------------[ СОЗДАЕМ И ПОДКЛЮЧАЕМ ИНТЕРФЕЙС ]---------------- #
	# ---------------------------------------------------------------- #

	//Для забора статистики нужен модуль UserAgent и url. Удовлетворяем зависимость
	return new Visits($this->useragent, $this->url, $this->config->get());
