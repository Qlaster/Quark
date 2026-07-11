<?php


	error_reporting(0);

	$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);

	$dir = $APP->files->jailPath(($_GET['path'] ?? '').DIRECTORY_SEPARATOR.($_GET['filename'] ?? ''));
	if (!$dir or !mkdir($dir, 0777, true))
	{
		die('Не удалось создать директории...');
	}

	die;
