<?php



	$content = $APP->controller->run('admin/autoinclude', $APP);
	error_reporting(E_ALL);
	$APP->object->collection($_GET['collection'])->create();


	header('Location: ../object');
