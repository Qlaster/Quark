<?php

	//метод формирования ссылки по входящим параметрам
	if (!function_exists('linker'))
	{
		function linker($params=[], $link="")
		{
			global $APP;
			$link   = $link ? "$link" : $APP->url->page();
			$params = array_merge($_GET, $params);
			foreach ($params as $name => &$_value) $_value = "$name=".urlencode($_value);
			return "$link?".implode('&', $params);
		}
	}

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

	//Статическая структура страницы — компаньон listing.ini, секция [view]
	$content = array_replace_recursive((array) $content, (array) $APP->config->get()['view']);

	//определяем формат вывода (таблица, плитка и т.д.)
	$format = $_GET['format'] ? $_GET['format'] : 'grid';

	//Сформируем меню (переключение формата сбрасывает страницу, остальные параметры сохраняются)
	foreach (['grid', 'table'] as $_format)
		$content['menu']['format']['list'][$_format]['link'] = linker(['format'=>$_format, 'offset'=>0]);

	$content['menu']['format']['list'][$format]['active'] = 'active';


	//Ссылка обновления — динамика
	$content['button']['btn-reload']['link'] = linker(['reload'=>1]);

	$cacheKey = $config['cache']['key']??'quark:mediafiles';

	//Если есть кеш - будем использовать его
	if (!($list = $APP->cache->get($cacheKey)) or $_GET['reload'])
	{
		$list = $APP->files->listing($mediaDIR);
		rsort($list);
		$APP->cache->set($cacheKey, $list, $config['cache']['time']??5000);
	}

	//Служебные файлы в списке не светим
	$list = array_diff((array) $list, ["$mediaDIR/.htaccess"]);

	//Серверный поиск — подстрока в пути, ищет по всему списку, не только по окну
	if ($like = trim((string) ($_GET['like'] ?? '')))
		$list = array_filter($list, function($f) use ($like) { return stripos($f, $like) !== false; });

	$count    = count($list);
	$fullsize = 0;
	foreach ($list as $_f) $fullsize += (int) @filesize($_f);


	//Пагинация
	$limit  = (int) ($config['page']['limit'] ?? 60);
	$offset = min(max(0, (int) ($_GET['offset'] ?? 0)), max(0, intdiv($count-1, $limit) * $limit));

	//Сконструируем [+ меню пагинации +], если у нас больше файлов чем выводим
	$content['menu']['pages']['list'] = [];

	if ($count > $limit)
	{
		$range = (int) ($config['page']['range'] ?? 10);

		for ($page = 0; ($step = $page*$limit) < $count; $page++)
		{
			$content['menu']['pages']['list'][$page]['head']   = $page+1;
			$content['menu']['pages']['list'][$page]['link']   = linker(['offset'=>$step]);
			$content['menu']['pages']['list'][$page]['active'] = $step==$offset;
		}

		//Потом вычислим срез окна страниц
		$startIndex = max(0, $offset/$limit - floor($range / 2));
		$endIndex   = min(count($content['menu']['pages']['list'])-1, $startIndex + $range-1);
		$startIndex = max(0, $endIndex - $range + 1);
		$maxPage    = count($content['menu']['pages']['list']);

		// Вырезаем сегмент
		$content['menu']['pages']['list'] = array_slice($content['menu']['pages']['list'], $startIndex, $range);

		//Обрамляем кнопочками "вперед"/"назад"
		if ($startIndex > 0)
			array_unshift($content['menu']['pages']['list'], ['head'=>'❮', 'link'=>linker(['offset'=>$offset-$limit])]);
		if ($endIndex < $maxPage-1)
			$content['menu']['pages']['list']['❯'] = ['head'=>'❯', 'link'=>linker(['offset'=>$offset+$limit])];
	}


	$mimelist = array_key_column('mime', $APP->objects->collection('admin')->get('mimeicon')['list']);

	//Собираем сводную информацию — только для файлов текущей страницы
	foreach (array_slice($list, $offset, $limit) as $file)
	{
		if (!$info = $APP->files->info($file)) continue;
		$listinfo[$file] = $info;
		//Иконка mime и тип файла
		$listinfo[$file]['icon']   = $mimelist[$info['mime']]['icon'] ?? "fa fa-file";
		$listinfo[$file]['format'] = $mimelist[$info['mime']]['format'];
	}

	$content['files']['list'] = $listinfo;
	$content['files']['tree'] = $APP->files->listingToTree($list);

	$content['files']['stat']['count']['text'] = $count ." ".$content['files']['stat']['count']['suffix'];
	$content['files']['stat']['size']['text']  = $APP->files->formatterSize($fullsize) ." ".$content['files']['stat']['size']['suffix'];

	//Форма поиска — action сохраняет формат и сбрасывает страницу
	$content['form']['filter']['action']        = linker(['offset'=>0]);
	$content['form']['filter']['like']['value'] = $like ?? '';

	//Локализация маркированных узлов
	$content = $APP->l10n->translate($content);

	$APP->template->file("admin/content/mediafiles/frame.$format.html")->display($content);
