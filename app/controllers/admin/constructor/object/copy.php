<?php


	$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);

	$collection	= urldecode($_GET['collection']);

	if (!$collection)
	{
		http_response_code(400);
		exit("Не указано имя коллекции");
	}

	$base_name = $_GET['collection'] . ' copy';
	$copy_name = $base_name;
	$attempt = 1;
	$max_attempts = 10;
	$collection_list = $APP->object->collection_list();

	// Подбор уникального имени
	while ($attempt <= $max_attempts) {
		if (!in_array($copy_name, $collection_list))
			break;
		
		$copy_name = $base_name . ' (' . $attempt . ')';
		$attempt++;
	}

	// Если все варианты заняты - добавить случайную строку
	if ($attempt >= $max_attempts)
		$copy_name = $base_name . ' ' . bin2hex(random_bytes(5));

	if (!$APP->object->collection($collection)->copy($copy_name))
	{
		http_response_code(400);
		exit("Ошибка при копировании коллекции");
	}

    header('Location: index');