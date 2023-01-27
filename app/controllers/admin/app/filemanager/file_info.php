<?php


	$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);

	//Файлы, который нужно открыть
	if (!file_exists($_GET['path']))
	{
		echo 'Элемент '.$_GET['path'].' не найден';
		return;
	}

	$path = $_GET['path'];
	$info = stat($path);

	//Имя
	$info['name'] 	= basename($path);
	//Full path
	$info['path'] 	= $path;
	//Создан
	$info['ctime'] 	= date('d.m.Y H:i:s', $info['ctime']);
	//Изменен
	$info['mtime'] 	= date('d.m.Y H:i:s', $info['mtime']);
	//Чтение/запись
	$info['write'] 	= is_writable($path);
	//Это директория?
	$info['dir'] 	= is_dir($path);

	//
	$sign = array(' Б', ' Кб', ' Мб', ' Гб', ' Тб');

	$i = 0;
	while ($info['size'] > 1024)
	{
		$info['size'] = $info['size'] / 1024;
		$i++;
	}
	$info['size'] = round($info['size'], 2) .$sign[(int)$i];




	$content['info'] 	= $info;
	//~ $themelink = $APP->url->home()."views/admin/";

	$html = 'admin/app/file_manager/file_manager_info.html';
	if ($_GET['version'] == 'min') $html = 'admin/app/file_manager/file_manager_info_min.html';



	$APP->template->file($html)->display($content);

	exit;
