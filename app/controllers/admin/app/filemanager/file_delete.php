<?php

	error_reporting(0);

	$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);

	try
	{
		$result = $APP->utils->files->remove($_GET['path']);
	}
	catch (Exception $e)
	{
		echo 'Ошибка удаления: ',  $e->getMessage(), "\n";
	}


	exit;
