<?php


	error_reporting(0);

	$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);

	//~ echo $_GET['path'];
	//~ die;
	if (!touch($_GET['path'].'/'.$_GET['filename']))
	{
		exit('Не удалось создать директории...');
	}

	exit;
