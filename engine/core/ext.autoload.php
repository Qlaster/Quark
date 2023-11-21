<?php

	/*
	 * Обеспечение автозагрузки классов
	 * 
	 */


	//Вариант синтаксиса 1:
	//Использоватеь пространства имен.
	//use lib/class

	//DEPRICATED: Вариант синтаксиса 2:
	//Прямой вызов метода lib
	//lib('lib/class')



	function lib($lib_path)
	{
		//Добавляем относительные пути к библиотеке
		//~ $lib_path = 'engine/lib/'.$lib_path;
		//~ $lib_path = 'engine/vendor/'.$lib_path;
		$lib_path = $_ENV['vendor']['path']."/$lib_path";

		//Если библиотека найдена, подключаем
		if (is_readable($lib_path))
		{
			require_once($lib_path);
			return true;
		}

		//Если библиотека не найдена - проверяем - может быть забыли указать расширение файла.
		//Для начала, выдираем расширение из существующего пути.
		$path_info = pathinfo($lib_path);

		//Если оно пустое -
		if (! isset($path_info['extension']))
		{
			//Пробуем добавить к нему расширение php и поискать
			if (is_readable($lib_path.'.php'))
			{
				//Да. Файл есть. Просто забыли указать расширение. Подключаем.
				require_once($lib_path.'.php');
				return true;
			}
		}

		//Что ж. Не повезло. Похоже библиотеки действительно нет.
		return false;
	}


	/*
	 * 
	 * Поддерка стандарта автозагрузки PSR4. Это автозагрузчик фреймворка "по умолчанию"
	 * 
	 * @param Autoload class name
	 * @return autoload status
	 * 
	 */
	function standardAutoloadFunc($className)
	{
		//spl_autoload_extensions(".php,.inc");
		// Заменить префикс пространства имен на базовую директорию.
		$prefix = '';
		$baseDir = $_ENV['vendor']['path'];

		if (substr($className, 0, strlen($prefix)) == $prefix)
		{
			$className = substr($className, strlen($prefix));
			$className = "$baseDir/$className";
		}

		// Заменить разделители пространства имен на разделители директорий.
		$className = str_replace('\\', DIRECTORY_SEPARATOR, $className);

		// Добавить расширение .php.
		$fileName = $className . ".php";

		// Проверить, что файл существует и его можно прочесть.
		if (is_readable($fileName)) require $fileName;
	}
	
	
	//Проверим наличие автозагрузчика composer, если он 
	if (is_readable($fileName = $_ENV['vendor']['path'].DIRECTORY_SEPARATOR.'autoload.php'))
	{
		require_once $fileName;
	}
		

	// Register the autoloader function.
	spl_autoload_register("standardAutoloadFunc");



