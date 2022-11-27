<?php


	$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);

	//Удалчяем заявленную коллекцию
	$APP->object->collection(base64_decode($_GET['collection']))->drop();

	header('Location: index');
