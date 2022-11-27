<?php


	$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);

	file_put_contents('1.txt', $_POST['data']);
