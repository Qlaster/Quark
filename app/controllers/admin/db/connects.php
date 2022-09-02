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

	$content['table']['db'] = $APP->db->config['connect'];
	foreach ($content['table']['db'] as $db => &$value) 
	{
		$value['link'] = "admin/db/table?base=$db";
		
		//проверим одключение
		if ($APP->db->connect($db)) 
		{
			$value['active'] = 'active';
		}
	}
	
	
	
	//Генерируем ссылки
	//~ foreach ($content['catalog']['page'] as $key => &$value) 
	//~ {
		//~ $value['link_view'] = 'admin/content/page/add?page='.$value['url'];
		//~ $value['link_edit'] = 'admin/content/page/edit?url='.$value['url'];
		//~ $value['link_del'] 	= 'admin/content/page/del?url='.$value['url'].'&lang='.$value['lang'];
	//~ }
	


	$APP->template->file('admin/dbmanager/db_connects.html')->display($content);

