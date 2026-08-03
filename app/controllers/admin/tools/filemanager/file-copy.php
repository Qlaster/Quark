<?php

	$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);

	//Буфер обмена файлового менеджера хранится в сессии
	$clipboard = &$_SESSION['filemanager']['clipboard'];

	//Запомнить объект в буфере
	if ($_GET['action'] == 'copy')
	{
		$src = $APP->files->jailPath($_GET['path'] ?? '');
		if (!$src or !file_exists($src)) exit('Исходный объект не найден');

		$clipboard = $src;
		exit(basename($src));
	}

	//Вставить объект из буфера в текущую директорию
	if ($_GET['action'] == 'paste')
	{
		$src = $clipboard;
		if (!$src or !file_exists($src)) exit('Буфер обмена пуст');

		$dstDir = $APP->files->jailPath($_GET['path'] ?? '');
		if (!$dstDir or !is_dir($dstDir)) exit('Целевая директория вне доступа');

		//Нельзя копировать директорию внутрь самой себя
		if ($src === $dstDir or strpos($dstDir.DIRECTORY_SEPARATOR, $src.DIRECTORY_SEPARATOR) === 0)
			exit('Нельзя скопировать объект внутрь самого себя');

		//Имя копии — уникальное при конфликте: name.ext -> name (1).ext
		$dst = $dstDir.DIRECTORY_SEPARATOR.uniqueName($dstDir, basename($src));

		exit(copyRecursive($src, $dst) ? '' : 'Ошибка копирования');
	}

	exit;


	//Рекурсивное копирование файла или директории
	function copyRecursive($src, $dst)
	{
		if (is_file($src)) return copy($src, $dst);

		if (!mkdir($dst, 0777, true)) return false;

		foreach (array_diff(scandir($src), ['.','..']) as $item)
			if (!copyRecursive($src.DIRECTORY_SEPARATOR.$item, $dst.DIRECTORY_SEPARATOR.$item))
				return false;

		return true;
	}

	//Подбирает свободное имя: name.ext -> name (1).ext -> name (2).ext
	function uniqueName($dir, $name)
	{
		if (!file_exists($dir.DIRECTORY_SEPARATOR.$name)) return $name;

		$pinfo = pathinfo($name);
		$base  = $pinfo['filename'];
		$ext   = isset($pinfo['extension']) ? '.'.$pinfo['extension'] : '';

		for ($i = 1; file_exists($dir.DIRECTORY_SEPARATOR.$candidate = "$base ($i)$ext"); $i++);
		return $candidate;
	}
