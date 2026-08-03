<?php


	//Защита от загрузки файлов вне разрешённых директорий
	$path = $APP->files->jailPath($_POST['path'] ?? '');
	if (!$path)
		throw new Exception('Ограничение доступа к целевой директории');


	$APP->files->uploadMove($path.DIRECTORY_SEPARATOR, filter_var($_POST['uniq'], FILTER_VALIDATE_BOOLEAN), '');
