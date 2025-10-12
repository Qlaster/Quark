<?php

	error_reporting(E_ALL & ~E_NOTICE);


	$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);


	$head = $_GET['head'];
	unset($APP->db->config['connect'][$head]);
	$APP->db->save();

	header("Location: {$_SERVER['HTTP_REFERER']}");
    exit;
