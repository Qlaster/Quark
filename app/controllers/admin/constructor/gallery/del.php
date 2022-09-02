<?php


	$content = $APP->controller->run('admin/autoinclude', $APP);
	
	//~ echo $_GET['collection'];
	//~ echo base64_decode($_GET['collection']);
	//~ //echo base64_decode($_GET['object']);
	
	//Удалчяем заявленную коллекцию
	$APP->object->collection('gallery')->del($_GET['name']);
	
	header('Location: index?name='.$_GET['name']);
