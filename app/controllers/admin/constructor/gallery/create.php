<?php



	$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);
	error_reporting(E_ALL);

	$APP->object->collection('gallery')->set($_GET['name'], null);


	header('Location: ../gallery');
