<?php

	$config   = $APP->files->config['media'];
	$mediaDIR = $config['folder']??'public';

	if ($_FILES)
	{
		//Загрузим новые файлы
		if (!$uploadDIR = $config['upload']) $uploadDIR = 'public/media';

		//~ $uploadDIR = $mediaDIR.DIRECTORY_SEPARATOR.$uploadDIR.DIRECTORY_SEPARATOR;
		if (filter_var($config['groupdate'], FILTER_VALIDATE_BOOLEAN))	$uploadDIR .= DIRECTORY_SEPARATOR.date('Y-m-d');

		if (!is_dir($uploadDIR))
			if (!mkdir($uploadDIR, 0777, true)) throw new Exception("Could not create directory $uploadDIR.");

		//Перемещаем в директорию для медиа, указан, нужно ли генерировать уникальное имя
		$APP->files->uploadMove($uploadDIR, filter_var($config['unique'], FILTER_VALIDATE_BOOLEAN), "");
	}

	//определяем формат вывода (таблица, плитка и т.д.)
	$format = $_GET['format'] ? $_GET['format'] : 'grid';

	//Сформируем меню
	foreach (['grid'=>'fa-th-large', 'table'=>'fa-th-list'] as $_format => $_icon)
	{
		$content['menu']['format']['list'][$_format]['icon'] = $_icon;
		$content['menu']['format']['list'][$_format]['link'] = "admin/content/mediafiles/listing?format=$_format";
	}
	$content['menu']['format']['list'][$format]['active'] = 'active';


	//Функциональные кнопки
	$content['button']['btn-delete']['head'] = "";
	$content['button']['btn-delete']['icon'] = "fa-trash-o";
	$content['button']['btn-delete']['link'] = "admin/content/mediafiles/listing?format=$format&reload=1";

	$content['button']['btn-reload']['head'] = "Обновить";
	$content['button']['btn-reload']['icon'] = "fa-refresh";
	$content['button']['btn-reload']['link'] = "admin/content/mediafiles/listing?format=$format&reload=1";




	$list = $APP->files->listing($mediaDIR);
	sort($list);
	$tree = $APP->files->listingToTree($list);

	//~ $tree = $APP->utils->files->listingToTree($list);
	//~ $tree = $APP->utils->files->tree('public');
	//~ $tree = $APP->files->tree('public');

	$mimelist = array_key_column('mime', $APP->objects->collection('admin')->get('mimeicon')['list']);

	$fullsize = 0;
	//Собираем сводную информацию о файлах
	foreach ($list as $file)
	{
		$listinfo[$file] = $info = $APP->files->info($file);
		//Иконка mime и тип файла
		$listinfo[$file]['icon']   = $mimelist[$info['mime']]['icon'] ?? "fa fa-file";
		$listinfo[$file]['format'] = $mimelist[$info['mime']]['format'];

		$fullsize += $info['bytes'];
	}

	unset($listinfo['public/.htaccess']);
	krsort($listinfo);

	$content['files']['list'] = $listinfo;
	$content['files']['tree'] = $tree;

	$content['files']['stat']['count']['icon'] = "fa fa-paperclip";
	$content['files']['stat']['count']['text'] = count($content['files']['list']) ." файлов";
	$content['files']['stat']['size']['icon'] = "fa fa-hdd-o";
	$content['files']['stat']['size']['text'] = $APP->files->formatterSize($fullsize) ." занято";


	$APP->template->file("admin/content/mediafiles/frame.$format.html")->display($content);


	//~ $APP->template->file('admin/content/mediafiles/frame.table.html')->display($content);
	//~ $APP->template->file('admin/content/mediafiles/tree.html')->display($content);
