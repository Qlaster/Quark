<?php

	error_reporting(0);

	$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);

	try
	{
		//jail-проверка пути выполняется внутри remove() (см. files.ini [jail])
		$result = $APP->files->remove($_GET['path']);
	}
	catch (Exception $e)
	{
		echo 'Ошибка удаления: ',  $e->getMessage(), "\n";
	}


	exit;
