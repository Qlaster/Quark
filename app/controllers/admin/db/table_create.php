<?php

	error_reporting(E_ALL & ~E_NOTICE);

	$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);
	//Прикрепляем страницы
	$content['catalog']['page'] = $APP->page->all($_GET['limit'], $_GET['offset']);


	//Узнаем имя будущей таблицы
	$tablename = $_POST['tablename'];
	unset($_POST['tablename']);

	//А в какаой базе?
	$base = $_GET['base'];


	//собираем поля таблицы
	foreach ($_POST as $_key => $_value)
	{
		$buffer = explode('_', $_key);
		$name = $buffer[0];
		$key = $buffer[1];

		$data[$key][$name] = $_value;
	}
	unset($buffer);


	foreach ($data as $key => $value) $buffer[$value['name']] = $value;
	$data = $buffer;

	$columns['id'] = ' INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL';
	foreach ($data as $key => $value) $columns[$value['name']] = $APP->db->config['patterns'][$value['type']]['type'];




	$APP->db->connect($base)->table($tablename)->create($columns);
	$APP->db->config['connect'][$base]['table'][$tablename] = $data;
	$APP->db->config_save();

	header("Location: table?base=$base&table=$tablename");
    exit;

	//OLD
    header("Location: {$_SERVER['HTTP_REFERER']}");
    exit;
