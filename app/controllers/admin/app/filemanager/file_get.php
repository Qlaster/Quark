<?php


	$content = $APP->controller->run('admin/autoinclude', $APP);
	
	//Файлы, который нужно открыть
	if (file_exists($_GET['file'])) echo file_get_contents($_GET['file']);
