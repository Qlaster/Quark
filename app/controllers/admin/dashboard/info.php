<?php

	$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);


	//Если не переадли дату выборки - выходим
	if (! isset($_GET['date'] )) return false;

	//Получаем дату, для которой необходимо выбрать статистику
	$date = $_GET['date'];

	$APP->visits->shear($date, $date);
	echo json_encode($APP->visits->statistics());



	exit;
