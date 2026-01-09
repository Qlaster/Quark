<?php


	$path  = realpath($_POST['path']).DIRECTORY_SEPARATOR;



	//Очень базово защитимся от загрузки файлов вне рабочей директории
	if (strpos($path, getcwd().DIRECTORY_SEPARATOR ) !== 0)
		throw new Exception('Ограничение доступа к целевой директории');


	$APP->utils->files->uploadMove("$path", filter_var($_POST['uniq'], FILTER_VALIDATE_BOOLEAN), '');
