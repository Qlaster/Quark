<?php


	$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);

	//~ echo $_GET['collection'];
	//~ echo base64_decode($_GET['collection']);
	//~ //echo base64_decode($_GET['object']);

	//Удалчяем заявленную коллекцию
	$APP->object->collection('form')->del($_GET['name']);

	header('Location: index');
