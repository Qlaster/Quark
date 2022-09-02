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



	$content = $APP->controller->run('admin/autoinclude', $APP);


	//Прикрепляем страницы
	//~ $content['catalog']['page'] = $APP->page->all($_GET['limit'], $_GET['offset']);
	$content['catalog']['article'] = $APP->object->collection('article')->all();
	
	//Генерируем ссылки
	foreach ($content['catalog']['article'] as $key => &$value) 
	{
		//~ $value['link_view'] = 'admin/content/page/add?page='.$value['url'];
		$value['link_edit'] = 'admin/content/article/edit?url='.$value['name'];
		$value['link_del'] 	= 'admin/content/article/del?url='.$value['name'];
	}
	


	
	$APP->template->file('admin/article_list.html')->display($content);

