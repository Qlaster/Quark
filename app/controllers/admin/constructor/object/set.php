<?php


	$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);

	//Получаем входные параметры
	$collection	= urldecode($_GET['collection']);
	$objectname	= urldecode($_GET['objectname']);

	if (!$objectname)
	{
		http_response_code(400);
		exit("Не указано имя объекта");
	}


	if ($_POST['object'])
	{
		$object = (array) json_decode($_POST['object'], true);

		if (!$APP->object->collection($collection)->set($objectname, $object))
		{
			http_response_code(400);
			exit("Ошибка сохранения");
		}
	}
