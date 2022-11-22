<?php
	namespace unit\provider;

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
	interface QProviderInterface
	{
		// Получение контента от поствщика данных
		public function execute($provider);
		// Вернет весь список доступных провайдеров
		public function listing();
	}

	# ---------------------------------------------------------------- #
	#                 РЕАЛИЗАЦИЯ	ИНТЕРФЕЙСА                         #
	# ---------------------------------------------------------------- #
	class Provider implements QProviderInterface
	{

		public $config;

		function __construct($config, $fileUtils)
		{
			//Устанавливаем конфиг по дефолту
			$this->config = $this->CorrectConfig($config);
			//Присоединяем интерфейс для управления файлами
			$this->utils = $fileUtils;
		}


		//Выполнение провайдера
		public function execute($provider, $APP=null)
		{
			//Определим, сетевой путь или локальный
			if (array_key_exists('scheme', parse_url($provider)))
			{
				//Если это сетевой путь, запросим данные по сети
				$result = file_get_contents($provider);
				//Проверим, является ли возвращенные объект JSON
				$data = json_decode($result);
				//Если является - вернем его, если нет - вернем ответ дословно
				return json_last_error() === JSON_ERROR_NONE ? $data : $result;
			}
			else //Для локальных путей просто выполним сценарий
			{
				//Строим полный путь
				$path = $this->config['folder'] .DIRECTORY_SEPARATOR. $provider;

				//Вызываем дословно
				if ( is_file($path) 		) return include $path;
				//Вызываем с php (если опустили расширение в нотации)
				if ( is_file($path.'.php')	) return include $path.'.php';
			}
		}

		public function listing($path='', $math="*.php")
		{
			if (!file_exists($this->config['folder'] .DIRECTORY_SEPARATOR. $path)) return [];

			$pathLength = mb_strlen($this->config['folder'].DIRECTORY_SEPARATOR.$path)+1;
			$providerlist = $this->utils->listing($this->config['folder'] .DIRECTORY_SEPARATOR. $path, $math);

			foreach ($providerlist as &$filePath)
				$filePath = mb_substr($filePath, $pathLength);

			return $providerlist;
		}

		//Получение списка всех доступных поставщиков контента
		//TODO: Предложеный код красив и отлично работает, но было найдено более производительное решение
		//~ public function listingE($path='', $math="*.php")
		//~ {
			//~ $path = $this->config['folder'] .DIRECTORY_SEPARATOR. $path;

			//~ $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
			//~ $files = array();
			//~ foreach ($rii as $file)
				//~ if (!$file->isDir())
				//~ {
					//~ $pathName = mb_substr($file->getPathname(), mb_strlen($this->config['folder'].DIRECTORY_SEPARATOR));
					//~ if (!$math)
						//~ $files[] = $pathName;
					//~ else
						//~ fnmatch($math, $file->getFilename()) ? $files[] = $pathName : null;
				//~ }
			//~ return $files;
		//~ }



		//Корректируем конфигурацию, заполняя недостающие параметры
		public static function CorrectConfig($config = array())
		{
			$default['folder'] 	= './controllers';
			$default['runlock'] = true;

			return array_merge($default, $config);
		}

	}



	# ---------------------------------------------------------------- #
	# --------------[ СОЗДАЕМ И ПОДКЛЮЧАЕМ ИНТЕРФЕЙС ]---------------- #
	# ---------------------------------------------------------------- #

	//Создаем класс управления контроллерами
	return new Provider($this->config->get(__file__), $this->utils->files);

