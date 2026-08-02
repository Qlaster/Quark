<?php

	foreach ((array) $_POST['files'] as $file)
	{
		if (!$file) continue;

		//Никаких сомнительных путей в имени файла
		if (strpos($file, '../') === false)
		{
			//Создадим полный путь к файлу
			$removeFile = getcwd().DIRECTORY_SEPARATOR.$file;
			//Определим рабочую директорию
			$dirname    = dirname($removeFile).DIRECTORY_SEPARATOR;

			unlink($removeFile);

			//Если директория пуста - тоже вытираем ее
			if (!glob($dirname)) rmdir($dirname);
		}

	}
