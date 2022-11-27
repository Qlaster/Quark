<?php

	$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);

	//Возвращаем менюшку
	//~ echo json_encode($APP->object->collection('admin')->get('mainmenu'));

	echo json_encode( $APP->object->collection('menu')->get(base64_decode($_GET['name'])));


	//~ $a = $APP->object->collection('admin')->get('mainmenu');
	//~ $APP->object->collection('menu')->set('lol', $a);

	die;

