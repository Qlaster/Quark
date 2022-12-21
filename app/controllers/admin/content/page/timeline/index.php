<?php

	//~ error_reporting(E_ALL & ~E_NOTICE);

	$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);


	//Подгружаем конфигурацию
	$config = $APP->config->get();

	//Получим все версии запрашиваемой страницы
	$versions = (array) $APP->page->versions($_GET['url'], $_GET['lang']);

	//Текущая используемая версия
	$actualPage = $APP->page->get($_GET['url'], $_GET['lang']);

	$previousPage = [];

	foreach ($versions as &$record)
	{
		//Получим страницу из истории
		$currentPage = $APP->page->get($_GET['url'], $_GET['lang'], $record['version']);
		//Если такой страницы уже не существует
		if ($currentPage === null) continue;

		//Если текущая версия актуальна - пометим ее
		if ($record['version'] == $actualPage['version'])
			$history[$record['version']]['active'] = true;

		//Скопируем страницу для детализации вывода
		$history[$record['version']]['page'] = $currentPage;
		//Возьмем заголовок той версии в качестве заголовка записи
		$history[$record['version']]['head'] = $currentPage['content']['title']['data'];
		//Удалим контент, для экономии озу
		unset($history[$record['version']]['page']['content']);

		//Сравним с предыдущей, что бы вычислисть изменения
		foreach ($currentPage['content'] as $varname => $data)
		{
			//Если эта переменная добавлена
			if (!isset($previousPage['content'][$varname]))
			{
				$history[$record['version']]['insert'][$varname] = $data;
				unset($previousPage['content'][$varname]);
				continue;
			}

			//Если эта переменная была изменена
			if ($previousPage['content'][$varname]['data'] != $data['data'])
			{
				$history[$record['version']]['update'][$varname] = $data;
				unset($previousPage['content'][$varname]);
				continue;
			}

			unset($previousPage['content'][$varname]);
		}

		//Оставшиеся переменные запишем в удаленные - их нет в текущей версии страницы
		if ($previousPage['content'])
			$history[$record['version']]['delete'] = $previousPage['content'];
		//Теперь это станет предыдущей версией
		$previousPage = $currentPage;
	}

	$content['history']['list'] = array_reverse($history);

	//~ $page =  $APP->page->get('index', '', '2021-11-17 15:47:35');

	//~ print_r($history); die;

	//~ print_r($versions); die;

	//Подгружаем локаль конфига
	//~ $content = array_merge($content, $config['ru']);

	//Получаем текущий адрес
	//~ $content['form']['edit']['url']['prefix'] = $APP->url->home();


	$content['title'] = 'История изменения страницы: '.$_GET['url'];


	//~ $themelink = $APP->url->home()."views/admin/";
	$APP->template->file('admin/content/page.timeline.html')->display($content);


