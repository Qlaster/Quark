<?php

	error_reporting(E_ALL & ~E_NOTICE);

	$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);
	//Прикрепляем страницы
	$content['catalog']['page'] = $APP->page->all($_GET['limit'], $_GET['offset']);


	$base = $_GET['base'];
	$table = $_GET['table'];

	$APP->db->connect($base)->table($table)->drop();


	header("Location: {$_SERVER['HTTP_REFERER']}");
    exit;


