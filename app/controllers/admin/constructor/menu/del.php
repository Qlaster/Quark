<?php


	$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);

	//~ echo $_GET['collection'];
	//~ echo base64_decode($_GET['collection']);
	//~ //echo base64_decode($_GET['object']);

	//Удалчяем заявленную коллекцию
	$APP->object->collection(base64_decode($_GET['collection']))->del(base64_decode($_GET['object']));

	header('Location: index?collection='.$_GET['collection']);
