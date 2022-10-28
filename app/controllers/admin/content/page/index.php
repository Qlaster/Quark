<?php

	//~ error_reporting(E_ALL & ~E_NOTICE);


	$content = $APP->controller->run('admin/autoinclude', $APP);

	//Собираем статистику (посещения) по страницам
	$APP->visits->shear('2018-1-1', date('Y-m-d'));
	$statistic = $APP->visits->statistics(false);
	$statistic['uri'][$APP->url->home().'index'] += $statistic['uri'][$APP->url->home()];

	//В качестве отправной точки для высчитавания посещаемости - возмем главную страницу
	$one_percent = $statistic['uri'][$APP->url->home().'index'] / 100;

	//~ echo $one_percent; die;

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
		$value['link_view'] = $APP->url->home().$value['url'];
		$value['link_edit'] = 'admin/content/page/edit?url='.$value['url'];
		$value['link_del'] 	= 'admin/content/page/del?url='.$value['url'].'&lang='.$value['lang'];
	}


	//~ $themelink = $APP->url->home()."views/admin/";
	$APP->template->file('admin/content/page_list.html')->display($content);

