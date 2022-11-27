<?php

	error_reporting(E_ALL & ~E_NOTICE);


	//~ //Загружаем главное меню
	//~ $menu = $APP->object->collection('admin')->get('mainmenu');
	//~ //Подключаем нужное языковое меню
	//~ $content['nav']['main'] = $menu['ru'];
	//~ //Указываем пункт меню, который раскрыть
	//~ $content['nav']['main']['list']['content']['active'] = true;
	//~ $content['nav']['main']['list']['content']['list']['pages']['active'] = true;
//~
	//~ $content['base'] = $APP->url->home();
	//~
	//~
	//~ $path = explode('/', $APP->url->page());
	//~ foreach ($path as $key => &$value) $tmp['list'][]['head'] = $value;
	//~ //Прикрепляем путь к контенту
	//~ $content['nav']['path'] = $tmp;



	$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);


	//Прикрепляем страницы
	//~ $content['catalog']['page'] = $APP->page->all($_GET['limit'], $_GET['offset']);
	//~ $content['catalog']['article'] = $APP->object->collection('article')->all(); //!!

	//Генерируем ссылки
	// foreach ($content['catalog']['article'] as $key => &$value)
	// {
	// 	//~ $value['link_view'] = 'admin/content/page/add?page='.$value['url'];
	// 	//~ $value['link_edit'] = 'admin/content/page/edit?url='.$value['url'];
	// 	//~ $value['link_del'] 	= 'admin/content/page/del?url='.$value['url'].'&lang='.$value['lang'];
	// }

	if ($_GET['url'] !== null)
	{

		$content['catalog']['article'] = $article = $APP->object->collection('article')->get($_GET['url']);

		print_r($content['catalog']['article']);

	}
	else $content['catalog']['article'] = $APP->object->collection('article')->all();



	$APP->template->file('admin/article_edit.html')->display($content);

