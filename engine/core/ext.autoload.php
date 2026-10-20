<?php

	/*
	 * Обеспечение автозагрузки классов
	 *
	 * Пакетные зоны перечислены в $_ENV['vendor']:
	 * 	path/path-имена = "engine/vendor"   — статические пакеты фреймворка
	 * 	app             = "vendor"          — composer-вендорка приложения
	 * Порядок ключей = приоритет поиска.
	 */


	/*
	 * Файловый примитив: подключить файл по относительному пути
	 * из любой пакетной зоны (первая нашедшаяся выигрывает).
	 *
	 * Использование — явный отложенный include, в т.ч. для файлов
	 * без классов (процедурные библиотеки, адаптеры):
	 * 	uses('sms/sms.ru/sms.ru.php');
	 * 	uses('sms/sms.ru/sms.ru');   //расширение можно опустить
	 *
	 * @param  string $path  путь относительно корня пакетной зоны
	 * @return bool          удалось ли найти и подключить файл
	 */
	function uses($path)
	{
		foreach ((array)$_ENV['vendor'] as $dir)
		{
			$file = "$dir/$path";

			//файл найден - подключаем
			if (is_readable($file)) { require_once $file; return true; }

			//забыли указать расширение - пробуем .php
			if (is_readable("$file.php")) { require_once "$file.php"; return true; }
		}

		return false;
	}


	/*
	 * Автозагрузчик PSR4 по умолчанию.
	 * Адаптер: имя класса → относительный путь → uses().
	 *
	 * @param  string $className  полное имя класса с неймспейсом
	 * @return bool               удалось ли найти и подключить класс
	 */
	function standardAutoloadFunc($className)
	{
		return uses(str_replace('\\', DIRECTORY_SEPARATOR, $className).'.php');
	}


	//Автолоадер composer - где бы ни лежал autoload.php в пакетных зонах
	foreach ((array)$_ENV['vendor'] as $dir)
		if (is_readable($fileName = "$dir/autoload.php")) require_once $fileName;



	// Register the autoloader function.
	spl_autoload_register("standardAutoloadFunc");

