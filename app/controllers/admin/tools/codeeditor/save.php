<?php


	if ($_POST['code'])
	{
		//Запись только внутри разрешённых директорий
		$fn = $APP->files->jailPath($_POST['code']['filename'] ?? '');
		echo ($fn and file_put_contents($fn, $_POST['code']['body']) !== false) ? 'Сохранение успешно' : 'Не удалось сохранить файл';
	}


	if ($_POST['config'])
	{
		$fn = $APP->files->jailPath($_POST['config']['filename'] ?? '');
		echo ($fn and file_put_contents($fn, $_POST['config']['body']) !== false) ? 'Сохранение успешно' : 'Не удалось сохранить файл';
	}
