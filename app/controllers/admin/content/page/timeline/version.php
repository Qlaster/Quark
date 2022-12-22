<?php

	//~ error_reporting(E_ALL & ~E_NOTICE);

	$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);


	//Получим все версии запрашиваемой страницы
	//~ $versions = (array) $APP->page->versions($_GET['url'], $_GET['lang']);

	//Текущая используемая версия
	$page = $APP->page->get($_GET['url'], $_GET['lang'], $_GET['version']);



	//Запустим контроллер рендеринга страницы
	$APP->controller->run('index', ['APP'=>$APP, 'page'=>$page]);
