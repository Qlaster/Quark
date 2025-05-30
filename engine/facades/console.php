<?php
	namespace unit\console;


	trait QconsoleTools
	{
		public $widthTterminal = 70;

		function print($line='')
		{
			echo $line.PHP_EOL;
		}

		function printH($line)
		{
			$widthTterminal = $this->widthTterminal;
			$tabulator = ($widthTterminal - mb_strlen($line))/2;
			if ($tabulator < 0) $tabulator = 0;

			$this->print();
			$this->print(str_repeat('=', $widthTterminal));
			$this->print(str_repeat(' ', $tabulator).$line);
			$this->print(str_repeat('=', $widthTterminal));
		}

		function printH2($line)
		{
			$widthTterminal = $this->widthTterminal;
			$tabulator = ($widthTterminal - mb_strlen($line))/2;
			if ($tabulator < 0) $tabulator = 0;

			$this->print();
			$this->print(str_repeat('-', $widthTterminal));
			$this->print(str_repeat(' ', $tabulator).$line);
			$this->print(str_repeat('-', $widthTterminal));
		}

		function printH3($line)
		{
			$widthTterminal = $this->widthTterminal;
			$tabulator = ($widthTterminal - mb_strlen($line))/2;
			if ($tabulator < 0) $tabulator = 0;

			$this->print();
			$this->print(str_repeat(' ', $tabulator).$line);
			$this->print(str_repeat('-', $widthTterminal));
		}

		function errorPrint($line)
		{
			$widthTterminal = $this->widthTterminal;
			$tabulator = ($widthTterminal - mb_strlen($line))/2;
			if ($tabulator < 0) $tabulator = 0;

			$this->print();
			$this->print(str_repeat('!', $widthTterminal));
			$this->print(str_repeat(' ', $tabulator).$line);
			$this->print(str_repeat('!', $widthTterminal));
		}
	}


	class QConsole
	{
		public $config = [];
		private $app;

		function __construct($config, $app=null)
		{
			$this->config = $config;
			$this->app    = $app;
			$this->tools  = new class {use QconsoleTools;};
		}

		//Создание резервной копии
		function backup($target=['app', 'engine'], $backupStorage='backups')
		{
			$this->tools->printH3("Создание резервной копии");
			$this->tools->print("Резервную копию платформы можно найти в директории backups");

			$bakupDir = $backupStorage.DIRECTORY_SEPARATOR.date("Y.m.d_H-i-s");
			if (!mkdir($bakupDir, 0775, true)) throw new \Exception('Directory creation denied');
			if (!file_exists($bakupDir)) throw new \Exception('Directory creation denied');
			if (!$target) throw new \Exception('Backup target directory not specified');

			//Возмем информацию из ядра о директории с приложением
			foreach ($target as $from)
			{
				$this->tools->print("Копируется $from");
				exec("cp -r '$from' '$bakupDir/$from'");
			}
		}

		//Слияние двух платформ с сохранением бд и конфигурации последней ($from, $to)
		function merge(...$args)
		{
			$from = current($args)[0];
			$to   = current($args)[1];

			if (!file_exists($from)) throw new \Exception('Source not specified');
			if (!file_exists($to))   throw new \Exception('Target not specified');

			//Проверим наличие ключевых компонентов в корневой диерктории обновляемой системы, иначе рискуем сломать по совместимости
			foreach ($this->config['update']['rootmarker'] as $rootmarker)
				if (! file_exists($to.DIRECTORY_SEPARATOR.$rootmarker)) throw new \Exception("Target directory '$to' does not contain platform");


			//Список файлов на обновление
			$listing = $this->app->utils->files->listing($from);

			function isRules($ruleses, $relativePath)
			{
				//Пройдемся по спецификации обновления и ищем подходящие правила
				foreach ($ruleses as $mask)
					if (fnmatch($mask, $relativePath)) return true;
			}

			//Пройдемся по файликам и ищем подходящие правила
			foreach ($listing as $key => $sourcefile)
			{
				//Относительнуй путь внутри обновляемой платформы
				$relativePath = mb_substr($sourcefile, mb_strlen($from)+1);
				//Полный путь внутри обновляемой платформы
				$fullPath = $to.DIRECTORY_SEPARATOR.$relativePath;

				//Если мы должны проигнорировать этот файл
				if (isRules($this->config['update']['ignore'], $relativePath)) continue;
				//Если мы должны произвести слияние конфигурации
				if (isRules($this->config['update']['merge'], $relativePath))
				{
					$configOld = (array)$this->app->config->get($fullPath);
					$configNew = (array)$this->app->config->get($sourcefile);
					//перезапишем конфигурацю во временной директории
					//Хитрость тройного указания конфига (старый, новый, старый) в том, что бы сохранить порядок элементов как в старом
					$this->app->config->set(array_replace_recursive($configOld, $configNew, $configOld), $sourcefile);
					//~ continue;
				}

				//Проложим путь для директории, если ее
				$mergeDir = $to.DIRECTORY_SEPARATOR.dirname($relativePath);
				if (!file_exists($mergeDir))
					if (!mkdir($mergeDir, 0775, true)) throw new \Exception("Directory '$mergeDir' creation denied");
				//Перемещаем файлы
				rename($sourcefile, $to.DIRECTORY_SEPARATOR.$relativePath);
			}

			return true;
		}

		function update()
		{

			//Если функция exec не включена или не работает - выведем исключение
			if (!((function_exists('exec')) and (exec('echo EXEC') == 'EXEC')))
				throw new \Exception('Function "exec" not wornikg!');

			//Получаем временную директорию
			$workDir  = sys_get_temp_dir().DIRECTORY_SEPARATOR.$this->config['update']['alias'].DIRECTORY_SEPARATOR;
			//Собираем путь к файлу дистрибутива
			$workFile = "$workDir/quark.zip";
			//Создаем веременную директорию размещения
			if (!file_exists($workDir))	mkdir($workDir);
			if (!file_exists($workDir))	throw new \Exception('Failed to create temporary directory');

			$listing = $this->app->utils->files->listing("$workDir/Quark-main");

			//Получаем ссылки на дистрибутив
			$distrLink = $this->config['update']['distr']['zip'];

			//Если ссылка на дистрибутив не найдена
			if (!$distrLink) throw new \Exception('Distribution link not found');

			$this->tools->printH('Запуск обновления платформы');

			$this->tools->printH3("Скачиваем свежий дистрибутив");
			$this->tools->print($distrLink);

			//Скачаиваем файл с предложенного хоста
			copy($distrLink, $workFile);
			$this->tools->print('Готово');

			$this->tools->printH3("Распаковываем дистрибутив с $workFile");
			//Распаковываем дистрибутив
			exec("unzip -o $workFile -d $workDir");
			//~ system("unzip -o $workFile -d $workDir");
			$this->tools->print('Готово');

			//Создаем резервную копию
			$this->backup();

			//Проверяем ключевые параметры перед обновлением
			$this->tools->printH3("Слияние обновлений");
			//Мержим проект
			$this->merge(["/tmp/quark/Quark-main/", getcwd()]);
		}

		function objects(...$args)
		{
			$args = $args[0];
			//Определяем команду
			$command = array_shift($args);

			if ('import' == mb_strtolower($command))
			{
				if (!$args) return $this->tools->print("Enter filename: objects import <filename.ini>");
				$result = [];
				//Пройдемся по всем переданным путям
				foreach ($args as $filename)
					if (is_file($filename) and is_readable($filename))
					{
						//Если это файл с json строкой
						$source = file_get_contents($filename);
						if (is_json($source) and $this->app->objects->import($source))
						{
							$collections = json_decode($source);
						}
						elseif ($collections = $this->app->config->get($filename) and is_array($collections))
						{
							$this->app->objects->import(json_encode($collections));
						}
						else
						{
							$this->tools->errorPrint("Error parse file: '$filename'");
						}

						$result = array_merge_recursive($result, $collections);
					}
					else
					{
						$this->tools->errorPrint("File '$filename' not found");
					}


					$this->tools->print("Import objects:");
					//Выведем список импортируемых объектов
					foreach ($result as $section => $objects)
					{
						$this->tools->print("\r\n[$section]");
						foreach ($objects as $name => $object)
							$this->tools->print("	| $name");
					}

					return true;
			}


			if ('export' == mb_strtolower($command))
			{
				if (!$args) return $this->tools->print("Enter filename: objects export <filename.ini>");
				//Получим все объекты системы
				$collectionsJSON = $this->app->objects->export();

				//Пройдемся по всем переданным путям
				foreach ($args as $filename)
				{
					//Если попросили импортировать в ini файл
					if (pathinfo($filename, PATHINFO_EXTENSION) == 'ini')
					{
						$this->app->config->set(json_decode($collectionsJSON, true), $filename);
					}
					else
					{
						file_put_contents($filename, $collectionsJSON);
					}

					if (!is_readable($filename))
					{
						return $this->tools->errorPrint("Error export file '$filename'. Is the file system writable?");
					}
				}



				$this->tools->print("Export objects:");
				//Выведем список импортируемых объектов
				foreach ($result = json_decode($collectionsJSON, true) as $section => $objects)
				{
					$this->tools->print("\r\n[$section]");
					foreach ($objects as $name => $object)
						$this->tools->print("	| $name");
				}
				return true;
			}
		}

		function service(...$args)
		{
			global $argv;
			$servicefile = $args[0][0];

			//Фальсифицируем массив $argv, что бы образение к сервису не воспринималось как запуск внутри фреймворка
			if ($_ENV['service']['limpid'])
			{
				$argv[0] = $argv[2];
				unset($argv[1], $argv[2]);
				$argv = array_values($argv);
			}

			if (!$servicefile or !is_readable($servicefile = $_ENV['service']['path'].DIRECTORY_SEPARATOR.$servicefile)) throw new \Exception("Service file not found or no access to file $servicefile");
			return require $servicefile;
		}


		function run(...$args)
		{
			$args = current($args);

			$APP = $this->app;

			$runfile = getcwd() . DIRECTORY_SEPARATOR .$args[0];

			//Дополняем расширение
			$runfile  = is_file($runfile.'.php') ? $runfile.'.php' : $runfile;

			return include($runfile);
		}
	}




	return new QConsole($this->config->get(), $this);
