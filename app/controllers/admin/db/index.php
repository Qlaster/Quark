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



	$APP->template->file('admin/dbmanager/db_construct.html')->display($content);

