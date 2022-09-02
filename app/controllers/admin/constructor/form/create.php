<?php



	$content = $APP->controller->run('admin/autoinclude', $APP);
	error_reporting(E_ALL);
	
	$APP->object->collection('form')->set($_GET['name'], null);


	header('Location: ../form');
