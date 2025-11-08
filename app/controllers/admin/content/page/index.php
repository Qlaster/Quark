<?php

	//~ error_reporting(E_ALL & ~E_NOTICE);


	$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);

	//Собираем статистику (посещения) по страницам (за поседние 4 месяца)
	$APP->visits->shear(date("Y-m-d", strtotime("-4 month")), date('Y-m-d'));
	$statistic = $APP->visits->statistics(false);
	$statistic['uri'][$APP->url->home().'index'] += $statistic['uri'][$APP->url->home()];

	//В качестве отправной точки для высчитавания посещаемости - возмем главную страницу
	$one_percent = $statistic['uri'][$APP->url->home().'index'] / 100;

	//~ echo $one_percent; die;
	$content['title'] = 'Работа со страницами сайта';

	//Прикрепляем страницы
	$buffer = $APP->page->all($_GET['limit'], $_GET['offset']);
	$content['catalog']['page'] = [];


		foreach ($buffer as $key => &$value)
		{
			$value['visits'] 	= (int) $statistic['uri'][$APP->url->home().$value['url']];
			if ($one_percent)
				$value['progress'] 	= $value['visits']/$one_percent;
			$content['catalog']['page'][$value['url']] = $value;

		}


	ksort($content['catalog']['page']);

	//Генерируем ссылки
	foreach ($content['catalog']['page'] as $key => &$value)
	{
		$value['link_view']		= $APP->url->home().$value['url'];
		$value['link_edit']		= 'admin/content/page/edit?url='.urlencode($value['url']);
		$value['link_del']		= 'admin/content/page/del?url='.urlencode($value['url']).'&lang='.$value['lang'];
		$value['link_timeline'] = 'admin/content/page/timeline?url='.urlencode($value['url']).'&lang='.$value['lang'];

		//Получим директорию с шаблонами
		$templateDir			= $APP->template->config['templates']['folder']; //.DIRECTORY_SEPARATOR
		$value['link_html']     = 'admin/tools/codeeditor?file='.$templateDir.DIRECTORY_SEPARATOR.urlencode($value['html']);
	}

	$content['menu']['tools'][1]['icon'] = "fa fa-wrench";
	$content['menu']['tools'][1]['button'][1]['head'] = "Очистить историю изменений";
	$content['menu']['tools'][1]['button'][1]['icon'] = "fa fa-trash";
	$content['menu']['tools'][1]['button'][1]['link'] = "admin/content/page/timeline/remove";



	//~ $themelink = $APP->url->home()."views/admin/";
	$APP->template->file('admin/content/page.list.html')->display($content);

