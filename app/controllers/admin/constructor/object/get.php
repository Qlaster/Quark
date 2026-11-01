<?php

	$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);



	//Получаем входные параметры
	$collection	= (urldecode($_GET['collection']));
	$object		= (urldecode($_GET['object']));
	$lang		= urldecode($_GET['lang'] ?? '');


	//Язык задан: промах по переводу → откат на базовую версию (каскад)
	echo json_encode($APP->object->collection($collection)->lang($lang ? [$lang, ''] : $lang)->get($object));



	die;
