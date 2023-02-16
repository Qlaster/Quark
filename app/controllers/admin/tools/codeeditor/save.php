<?php


	if ($_POST['code'])
	{
		echo file_put_contents($_POST['code']['filename'], $_POST['code']['body']) ? 'Сохранение успешно' : 'Не удалось сохранить файл';
	}


	if ($_POST['config'])
	{
		echo file_put_contents($_POST['config']['filename'], $_POST['config']['body']) ? 'Сохранение успешно' : 'Не удалось сохранить файл';
	}
