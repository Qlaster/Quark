<?php
	
	$content = $APP->controller->run('admin/autoinclude', $APP);
	
	

	//Получаем входные параметры
	$collection	= (urldecode($_GET['collection']));
	$object		= (urldecode($_GET['object']));
	

	echo json_encode($APP->object->collection($collection)->get($object));
	
	
	
	die;
