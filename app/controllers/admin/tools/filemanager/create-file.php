<?php


	$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);

	//~ echo $_GET['path'];
	//~ die;
	$f = $APP->files->jailPath(($_GET['path'] ?? '').DIRECTORY_SEPARATOR.($_GET['filename'] ?? ''));
	if (!$f or !touch($f))
	{
		exit('Не удалось создать директории...');
	}

	exit;
