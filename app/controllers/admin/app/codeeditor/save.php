<?php


	$file = $_POST['filename'];
	$code = $_POST['code'];


	//~ print_r($_POST);

	//~ print_r($_REQUEST);
	
	if (file_put_contents($file, $code))
	{
		echo 'Сохранение успешно';
		return true;
	}
	else
	{
		echo 'Не удалось сохранить файл';
		return true;
	}

	
