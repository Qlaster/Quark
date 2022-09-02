<?php
	
	//Создадим русское меню админки
	


//~ 
	//~ //Раздел "Мониторинг"
	//~ $section['head'] = "Мониторинг";
	//~ $section['link'] = "admin/content";
	//~ $section['image'] = "fa fa-th-large";
	//~ $menu['list']['dash'] = $section;
	//~ unset($section);
	//~ 
	//~ 
	//~ 
	//~ //Раздел "Статистика"
	//~ $section['head'] = "Статистика";
	//~ $section['link'] = "admin/statistic";
	//~ $section['image'] = "fa fa-bar-chart-o";
	//~ 
	//~ $node['head'] = "Страницы";
	//~ $node['link'] = "admin/content/page";
	//~ $section['list']['page'] = $node;
	//~ 
	//~ $menu['list']['statistic'] = $section;
	//~ unset($section);
	//~ 
	//~ 
//~ 
	//~ //Раздел "Контент"
	//~ $section['head'] = "Контент";
	//~ $section['link'] = "admin/content";
	//~ $section['image'] = "fa fa-file-text-o";
	//~ 
	//~ $node['head'] = "Страницы";
	//~ $node['link'] = "admin/content/page";
	//~ $section['list']['pages'] = $node;
	//~ 
	//~ $node['head'] = "Структура";
	//~ $node['link'] = "admin/content/tree";
	//~ $section['list']['tree'] = $node;
	//~ 
	//~ $node['head'] = "Статьи";
	//~ $node['link'] = "admin/content/article";
	//~ $section['list']['articles'] = $node;
	//~ 
//~ 
	//~ 
	//~ $menu['list']['content'] = $section;
	//~ unset($section);
	//~ 
	//~ 
	//~ 
	//~ //Раздел "Конструктор"
	//~ $section['head'] = "Конструктор";
	//~ $section['link'] = "admin/constract";
	//~ $section['image'] = "fa fa-legal";
	//~ 
	//~ $node['head'] = "Объекты";
	//~ $node['link'] = "admin/constract/object";
	//~ $section['list']['contructor'] = $node;
	//~ 
	//~ $menu['list']['construct'] = $section;
	//~ unset($section);
	//~ 
	//~ 
//~ 
	//~ //Раздел "Приложения"
	//~ $section['head'] = "Приложения";
	//~ $section['link'] = "admin/app";
	//~ $section['info'] = "3";
	//~ $section['image'] = "fa fa-desktop";
	//~ 
	//~ $node['head'] = "Фоторедактор";
	//~ $node['link'] = "admin/app/imageeditor";
	//~ $section['list'][] = $node;
	//~ 
	//~ 
	//~ $node['head'] = "Редактор кода";
	//~ $node['link'] = "admin/app/code_editor";
	//~ $section['list'][] = $node;
	//~ 
	//~ $node['head'] = "Файловый менеджер";
	//~ $node['link'] = "admin/app/fwiews";
	//~ $section['list'][] = $node;
	//~ 
	//~ $menu['list']
//~ 
	//~ 
//~ 
	//~ 
//~ 
//~ 
	//~ $menu['list']['components'] = $section;
	//~ unset($section);
	//~ 
	//~ 
	//~ 
	//~ 
	//~ 
	//~ //Раздел Параметры
	//~ $section['head'] = "Параметры";
	//~ $section['link'] = "admin/option";
	//~ $section['image'] = "fa fa-sliders";//"fa fa-toggle-on";
	//~ 
		//~ 
	//~ 
	//~ $node['head'] = "Языки";
	//~ $node['link'] = "admin/option/lang";
	//~ $section['list'][] = $node;
	//~ 
	//~ $node['head'] = "Пользователи";
	//~ $node['link'] = "admin/option/user";
	//~ $section['list'][] = $node;
	//~ 
	//~ $node['head'] = "Языковая политра";
	//~ $node['link'] = "admin/option/library";
	//~ $section['list'][] = $node;	
//~ 
	//~ $menu['list']['option'] = $section;
	//~ unset($section);
	//~ 
	//~ //Раздел Документация
	//~ $section['head'] = "Документация";
	//~ $section['link'] = "admin/manual";
	//~ $section['image'] = "fa fa-graduation-cap";//"fa fa-toggle-on";
	//~ 
	//~ $node['head'] = "Быстрый старт";
	//~ $node['link'] = "admin/manual/statrup";
	//~ $section['list'][] = $node;	
	//~ 
	//~ $node['head'] = "Языковая политра";
	//~ $node['link'] = "admin/manual/library";
	//~ $section['list'][] = $node;	
//~ 
	//~ $menu['list']['manual'] = $section;
	//~ unset($section);
	//~ $node['head'] = "Частые вопросы";
	//~ $node['link'] = "admin/manual/lang";
	//~ $section['list'][] = $node;
	//~ 
	//~ $node['head'] = "Языковая политра";
	//~ $node['link'] = "admin/manual/library";
	//~ $section['list'][] = $node;	
//~ 
	//~ $menu['list']['manual'] = $section;
	//~ unset($section);
	//~ ['app'] = $section;
	//~ unset($section);
	//~ 
	//~ 
//~ 
	//~ //Раздел "Конфигурация" (компоненты)
	//~ $section['head'] = "Компоненты";
	//~ $section['link'] = "admin/components";
	//~ $section['image'] = "fa fa-cogs";
	//~ 
	//~ $node['head'] = "Контроллеры";
	//~ $node['link'] = "admin/components/controller";
	//~ $section['list']['controllers'] = $node;	
	//~ 
	//~ $node['head'] = "Библиотеки";
	//~ $node['link'] = "admin/components/library";
	//~ $section['list']['library'] = $node;	
	//~ 
	//~ 
	//~ $node['head'] = "Модули";
	//~ $node['link'] = "admin/components/unit";
	//~ $section['list']['units'] = $node;
	//~ 
//~ 
	//~ 
//~ 
	//~ 
//~ 
//~ 
	//~ $menu['list']['components'] = $section;
	//~ unset($section);
	//~ 
	//~ 
	//~ 
	//~ 
	//~ 
	//~ //Раздел Параметры
	//~ $section['head'] = "Параметры";
	//~ $section['link'] = "admin/option";
	//~ $section['image'] = "fa fa-sliders";//"fa fa-toggle-on";
	//~ 
		//~ 
	//~ 
	//~ $node['head'] = "Языки";
	//~ $node['link'] = "admin/option/lang";
	//~ $section['list'][] = $node;
	//~ 
	//~ $node['head'] = "Пользователи";
	//~ $node['link'] = "admin/option/user";
	//~ $section['list'][] = $node;
	//~ 
	//~ $node['head'] = "Языковая политра";
	//~ $node['link'] = "admin/option/library";
	//~ $section['list'][] = $node;	
//~ 
	//~ $menu['list']['option'] = $section;
	//~ unset($section);
	//~ 
	//~ //Раздел Документация
	//~ $section['head'] = "Документация";
	//~ $section['link'] = "admin/manual";
	//~ $section['image'] = "fa fa-graduation-cap";//"fa fa-toggle-on";
	//~ 
	//~ $node['head'] = "Быстрый старт";
	//~ $node['link'] = "admin/manual/statrup";
	//~ $section['list'][] = $node;	
	//~ 
	//~ $node['head'] = "Языковая политра";
	//~ $node['link'] = "admin/manual/library";
	//~ $section['list'][] = $node;	
//~ 
	//~ $menu['list']['manual'] = $section;
	//~ unset($section);
	//~ $node['head'] = "Частые вопросы";
	//~ $node['link'] = "admin/manual/lang";
	//~ $section['list'][] = $node;
	//~ 
	//~ $node['head'] = "Языковая политра";
	//~ $node['link'] = "admin/manual/library";
	//~ $section['list'][] = $node;	
//~ 
	//~ $menu['list']['manual'] = $section;
	//~ unset($section);
	//~ 
	
	
	//Добавляем русское меню 
	
	//print_r($menu);
	
	//print_r($APP->config->get());
	
	//die;
	
	//$adminmenu['ru'] = $menu;
	$adminmenu['ru']['list'] = $APP->config->get();
	
	
	
	
	
	//Возвращаем меню
	return $adminmenu;
	
