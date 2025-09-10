<?php

	$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);

	$jsonData = $APP->objects->collection($_GET['collection'])->export($_GET['object']);
	$filename = $_GET['collection'] || $_GET['object'] ? $_GET['collection']." ".$_GET['object'] : 'All collections';

	header($_SERVER["SERVER_PROTOCOL"] . " 200 OK");
	header("Cache-Control: public"); // needed for internet explorer
	header("Content-Type: application/json");
	header("Content-Transfer-Encoding: Binary");
	header("Content-Length:".strlen($jsonData));
	header("Content-Disposition: attachment; filename={$filename}.json");

	echo $jsonData;
