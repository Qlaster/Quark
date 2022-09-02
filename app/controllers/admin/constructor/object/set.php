<?php
	
	
	$content = $APP->controller->run('admin/autoinclude', $APP);


	//Получаем входные параметры
	$collection	= urldecode($_GET['collection']);
	$objectname	= urldecode($_GET['objectname']);
	
	
	//~ echo 1; die;
	if ($_POST['object'])
	{
		$object = (array) json_decode($_POST['object'], true);				
		print_r($object); 
		//~ var_dump($object);
		echo ($APP->object->collection($collection)->set($objectname, $object));
	}
