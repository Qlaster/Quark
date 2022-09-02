<?php

	/*
	
			Расширение ядра позволяет подключать библиотеки.  
		
		В общем смысле, библиотека - это набор инструментов для решения каких либо задач представленный, 
		наборами функций и/или классов оформленных в файл. Все файлы с классами и функциями складываются 
		в папку с определнным именем (названием библиотеки). Такая папка с набором утилит  -  библитека.
		Библиотеки позволяют группировать схожие инструменты в один логический блок. Отличительная черта 
		библиотек - их необходимо явно подключать через функцию lib.  Они не собираются самостоятельно, 
		как модули и расширения.  Явный вызов позволяет включать только те компоненты, которые нужны для
		работы сейчас. Подключить библиотеку можно в любом месте программы по необходимости.  
		
		Хорошим решением будет вызывать компонеты библиотеки при появлении явной необходимости. Не нужно
		Вызывать каскад классов библиотеки "про запас". В случае невостребованности - они съедят память,
		но ничего не дадут взамен. Не нужно боятся переподключений библиотек. Подключенная один раз - 
		она останется в памяти и не будет включена еще раз, даже при повторном вызове.
		
	
	*/


	//Вариант синтаксиса 1:
	//Использоватеь пространства имен.
	//use lib/class

	//Вариантов синтаксиса 2:
	//Прямой вызов метода lib
	//lib('lib/class')

	
	
	function lib($lib_path)
	{
		//Добавляем относительные пути к библиотеке
		//~ $lib_path = 'engine/lib/'.$lib_path;
		$lib_path = 'engine/vendor/'.$lib_path;
		
		
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
	
	
	
	// "Стандартный" автозагрузчик для composer и тд.
	function standardAutoloadFunc($className) 
	{
		// Заменить префикс пространства имен на базовую директорию.
		$prefix = '';
		$baseDir = 'engine/vendor/';
		if (substr($className, 0, strlen($prefix)) == $prefix) 
		{
			$className = substr($className, strlen($prefix));
			$className = $baseDir . $className;
		}

		// Заменить разделители пространства имен на разделители директорий.
		$className = str_replace('\\', DIRECTORY_SEPARATOR, $className);
	  
		// Добавить расширение .php.
		$fileName = $className . ".php";
	  
		// Проверить, что файл существует и его можно прочесть.
		if (is_readable($fileName)) 
		{
			// Включить файл.
			require $fileName;
		} 
	}

	// Register the autoloader function.
	spl_autoload_register("standardAutoloadFunc");
