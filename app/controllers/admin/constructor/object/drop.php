<?php


	$content = $APP->controller->run('admin/autoinclude', $APP);
	
	//Удалчяем заявленную коллекцию
	$APP->object->collection($_GET['collection'])->drop();
	
	header('Location: index');
