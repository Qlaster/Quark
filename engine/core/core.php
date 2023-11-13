<?php

	#[AllowDynamicProperties]
	class APP extends stdClass
	{
		//Конфигурация ядра
		public $core_config = [];

		function __construct($path_ext='', $paths_facades=['engine/facades'], $path_vendor='engine/vendor')
		{
			//Запоминаем путь, где лежат файлы модулей
			$this->core_config['path_units']  = (array) $paths_facades;
			$this->core_config['path_vendor'] = $path_vendor;

			//Подключаем расширения
			$this->__includeExtension($path_ext);
		}

		//Подгрузка модуля
		public function __get($unit)
		{
			//Пройдемся по всем директориям моделей, что бы найти нужный модуль
			foreach	($this->core_config['path_units'] as $interfaceAlias => $modelDir)
			{
				//Строим полный путь к файлу модуля
				$filename = $modelDir ? $modelDir.DIRECTORY_SEPARATOR."$unit.php" : "$unit.php";

				//Проверим наличие
				if (is_readable($filename))
				{
					//..и перецепим интерфейс, если найдена реализация
					$this->$unit = include($filename);
				}
			}

			//Проверяем наличие файла
			if (!$this->$unit) throw new Exception("Попытка загрузить интерфейс '$unit' провалилась. Возможно отсутствует файл или к нему нет доступа.");
			return $this->$unit;
		}

		//Обработка ситуации, когда попытались вызвать метод, который еще не зарегистрирован в ядре.
		public function __call($method, $args)
		{
			throw new Exception("Метод '$method' не поддерживается ядром. Аргументы вызова: ");
			var_dump($args);
		}

		public function __facades()
		{
			//Пройдемся по всем директориям моделей, что бы найти нужный модуль
			foreach	($this->core_config['path_units'] as $interfaceAlias => $modelDir)
				foreach (glob($modelDir."/*.php") as $filename)
				{
					//Получаем название модуля из имени файла
					$facade = basename($filename, '.php');
					$result[$facade] = $filename;
				}
			return $result;
		}

		private function __includeExtension($dir=__DIR__)
		{
			//Подключаем расширения ядра
			foreach (glob($dir."/ext.*.php") as $filename)
			{
				//Включаем файл в состав приложения
				require($filename);
			}
		}
	}
