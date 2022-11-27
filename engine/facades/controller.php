<?php
	namespace unit\controller;

	/*
		Алгоритм работы механизма поддержки контроллеров следующий:

		1) --------------
		Путь вначале расценивается как путь до папки с контроллером и пытается вызваться метод по умолчанию (обычно index.php)
		Т.е. если есть папка и файл с одинаковым именем, папка приоритетнеею Но если в ней нет метода index - она игнорируется.

		2) --------------
		Если папки нету. "Откусываем" последний элемент пути. Смотрим имя последнего элемента. Ищем его.
		Сначал добавив php расширение, а если его нет, то дословно, как передали.

		3) ---------------
		Если провалились на проверке файлов, то проверяем наличие контроллера (базового пути)
		Если базовый путь до контроллера соблюден и он существует, то вызываем базовый метод обработчика контроллера, указанный в конфиге

		config['folder']	- папка с контроллерами
		config['runlock']	- запретить в именах контроллеров конструкции рода ../../controller. По сути, запрещает вызов методов выше папки с контроллерами
		config['handler']	- дефолтный обработчик событий контроллера
		config['cascade']	- каскадная обработка контроллеров (если контроллер-дочка не существует, обработка перенаправляется контроллеру-родителю)
	*/

	# ---------------------------------------------------------------- #
	#                 ЭКСПОРТИРУЕМ 	ИНТЕРФЕЙС                          #
	# ---------------------------------------------------------------- #
	interface QControllerInterface
	{
		// Запуск контроллера
		public function run($controller);
	}

	# ---------------------------------------------------------------- #
	#                 РЕАЛИЗАЦИЯ	ИНТЕРФЕЙСА                         #
	# ---------------------------------------------------------------- #
	class Controller implements QControllerInterface
	{

		public $config;

		function __construct($config)
		{
			//Устанавливаем конфиг по дефолту
			$this->config = $this->CorrectConfig($config);
		}

		//Превращает адресный путь в путь к контроллеру
		public function realpath($controller='')
		{
			//Корректируем конфиг. Если опции нет, то устанавливаем по дефолту.
			$this->config = $this->CorrectConfig($this->config);

			//раскрывает все символические ссылки, переходы типа '/./', '/../' и лишние символы '/' в пути path, возвращая канонизированный путь к файлу.
			$path = $this->filtering($controller);

			//Строим полный путь
			$path = $this->config['folder'] .DIRECTORY_SEPARATOR. $path;

			//Вызываем с php.
			if ( is_file($path.'.php')	) return $path.'.php';
			//Вызываем без php (обратились по имени, не указав расширение)
			if ( is_file($path) 		) return $path;

			//Если обратились к папке, в которой лежит обработчик по умолчанию
			if ( (is_dir($path)) and ( is_file($path.DIRECTORY_SEPARATOR.$this->config['handler']) ) ) //and (substr($controller, -1) == '/')
			{
				//Случай №1. Нужно просто вызвать дефолтный метод контроллера
				return $path.DIRECTORY_SEPARATOR.$this->config['handler'];
			}
			return $path;
		}

		//Запуск контроллера
		public function run($controller='', array $_VARS=[])
		{
			//Создадим переменные окружения, которые передали для исполения
			foreach ($_VARS as $_enviroment_var_name => $_enviroment_var_value) $$_enviroment_var_name = $_enviroment_var_value;
			unset($_enviroment_var_name, $_enviroment_var_value);

			if (! is_readable($ctrl = $this->realpath($controller))) return null;

			$result = include $ctrl;
			return ($result === null) ? true : $result;
		}

		public function check($controller)
		{
			if (!function_exists('exec')) return null;
			try
			{
				$controller = escapeshellarg($controller);
				$res = exec("php -l '$controller'");
				return (bool) (mb_strcut($res, 0, 16) == 'No syntax errors');
			}
			catch (Error $e)
			{
				return false;
			}
		}


		//Каскадный запуск контроллеров до первого сработавшего
		public function Cascade($URL, $Q=null)
		{

		}


		//Корректируем конфигурацию, заполняя недостающие параметры
		public static function CorrectConfig($config = array())
		{
			$result = $config;

			if ( !isset($config['folder']) 	) 	$result['folder'] 	= './controllers';
			if ( !isset($config['runlock'])	)	$result['runlock'] 	= true;
			if ( !isset($config['handler'])	)	$result['handler'] 	= 'index.php';
			if ( !isset($config['cascade'])	)	$result['cascade'] 	= true;

			return $result;
		}


		//Получить конфиг по умолчанию
		public function GetDefaultConfig($config = array())
		{

		}


		//Механизм проверки пути на валидность.
		private function filtering($path)
		{
			//Пока глупо уберем двойную и одинарную точки. Хах! Метод с кучей багов, который даже обсуждать не стоит.
			//while ( strpos("/$path/", '/../') !== false ) $path = str_replace('..', '', $path);


			//Удалим возможность возврата за корневую директорию.
			//Для этого, разберем путь на составные части:
			$dir = explode(DIRECTORY_SEPARATOR, $path);

			//Чистанем от вторичных директорий
			$dir = array_diff($dir, ['', '.', '..']);

			//Собираем готовый путь
			$res_path = implode(DIRECTORY_SEPARATOR, $dir);

			//Если первоначальный вариант пути заканчивался на /, то мы должны поправить эту ситуацию, вернув его на место.
			if ( substr($path, -1) == DIRECTORY_SEPARATOR ) $res_path .= DIRECTORY_SEPARATOR;

			//до тех пор, пока в строке есть хоть один двойной повтор слешей - заменять их на одинарный.
			while ( strpos($res_path, '//') !== false )
				$res_path = str_replace('//', '/', $res_path);

			//Вернем готовый путь
			return $res_path;
		}

	}


	# ---------------------------------------------------------------- #
	# --------------[ СОЗДАЕМ И ПОДКЛЮЧАЕМ ИНТЕРФЕЙС ]---------------- #
	# ---------------------------------------------------------------- #

	//Создаем класс управления контроллерами
	return new Controller($this->config->get(__file__), $this);

