<?php
	
	$content = $APP->controller->run('admin/autoinclude', $APP);

	$jsonData = $APP->objects->export();

    header($_SERVER["SERVER_PROTOCOL"] . " 200 OK");
    header("Cache-Control: public"); // needed for internet explorer
    header("Content-Type: application/json");
    header("Content-Transfer-Encoding: Binary");
    header("Content-Length:".strlen($jsonData));
    header("Content-Disposition: attachment; filename=Objects.json");

	echo $jsonData;
