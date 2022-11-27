<?php

	$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);


	if ($APP->objects->import($_POST['jsonData']))
	{
		echo "OK";
	}
	else
	{
		echo "Ошибка загрузки списка объектов";
	}

	exit();
