<?php


	error_reporting(0);

	$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);

	if (!mkdir($_GET['path'].'/'.$_GET['filename'], 0777, true))
	{
		die('Не удалось создать директории...');
	}

	die;
