<?php
    
    $content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);

	$collection	= $_POST['collection'];
	$nameA	= $_POST['object'] ? $_POST['object'] : $_POST['new_name'];
	$nameB = $_POST['object'] ? $_POST['new_name'] : null;

	if (!$collection || !$nameA)
	{
		http_response_code(400);
		exit("Не указано имя коллекции");
	}

	if (!$APP->object->collection($collection)->rename($nameA, $nameB))
	{
		http_response_code(400);
		exit("Ошибка при переименовании коллекции");
	}

	header('Location: index' . ($_POST['object'] ? '?collection='.$collection : ''));