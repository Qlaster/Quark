<?php


	$content = $APP->controller->run('admin/autoinclude', $APP);
	
	file_put_contents('1.txt', $_POST['data']);
