<?php

	error_reporting(E_ALL & ~E_NOTICE);


	$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);


	$base  = $_GET['base'];
	$table = $_GET['table'];
	$content['table']['data']['name']	= $_GET['base'];


	$content['catalog']['types'] = $APP->db->config['patterns'];


	$APP->template->file('admin/dbmanager/db_construct.html')->display($content);

