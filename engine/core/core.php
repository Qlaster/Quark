<?php

	#[AllowDynamicProperties]
	class APP extends stdClass
	{
		//Конфигурация ядра
		public $core_config = array(); 
		
	
	
		function __construct($path_ext='', $paths_facades=['engine/facades'], $path_vendor='engine/vendor')
		{
			//Запоминаем путь, где лежат файлы модулей
			$this->core_config['path_units']  = $paths_facades;
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
				$filename = $modelDir . "/$unit.php";
				
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
			echo "Указанный метод не поддерживается ядром: ";
			var_dump($method, $args);
		}


		public function __includeUnits($dir)
		{	
			//Подключаем модули расширений
			foreach (glob($dir."/*.php") as $filename) 
			{
				
				//Получаем название модуля из имени файла
				$unit = basename($filename, '.php');
				
				//Подключем интерфейс, который модуль предлагает использовать и регистрируем его в качестве метода приложения
				//Данный подход позволяет обращаться к функциональности модуля вызвав метод приложения.
				$this->$unit = include($filename);
			}
		}
		
		private function __includeExtension($dir=__DIR__)
		{				
			//Подключаем расширения ядра
			foreach (glob($dir."/ext_*.php") as $filename) 
			{
				//Включаем файл в состав приложения
				require($filename);
			}
		}
		
		
		
	}
