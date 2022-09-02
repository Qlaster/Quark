<?php


	$content = $APP->controller->run('admin/autoinclude', $APP);
	
	//~ echo $_GET['collection'];
	//~ echo base64_decode($_GET['collection']);
	//~ //echo base64_decode($_GET['object']);
	
	//Удалчяем заявленную коллекцию
	$APP->object->collection(urldecode($_GET['collection']))->del(urldecode($_GET['object']));
	
	header('Location: index?collection='.$_GET['collection']);
