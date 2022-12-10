<?php

	$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);

	if ($_GET['name'] && $_GET['new-name'])
	{
		//Загрузим галлерею
		$gallery = $APP->object->collection('gallery')->get($_GET['name']);
		$APP->object->collection('gallery')->set($_GET['new-name'], $gallery);
	}

	header("Location: ".$_SERVER['HTTP_REFERER']);
