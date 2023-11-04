<?php



	$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);
	error_reporting(E_ALL);

	//Создадим галерею, убедившись что ее не существует
	if (! $APP->object->collection('gallery')->get($_GET['name']))
		$APP->object->collection('gallery')->set($_GET['name'], null);
	

	header('Location: ../gallery?name='.urlencode($_GET['name']));
