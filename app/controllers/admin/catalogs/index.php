<?php

	$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);

	$content['title'] = 'Каталоги';
	$content['catalogs']['list']  = $APP->catalog->listing();



	foreach ($content['catalogs']['list'] as $catalogName => &$_catalog)
	{
		$_catalog['icon'] = $_catalog['icon'] ?? 'fa-table';

		if ($APP->db->connect($_catalog['db']) and ($_catalog['table']))
		{
			$_catalog['link']   = "admin/catalogs/view?name=$catalogName";
			$_catalog['status']['connect'] = 'active';
			continue;
		}
		$_catalog['status']['icon'] = 'fa-plug';
		$_catalog['status']['tone'] = 'text-danger';
		$_catalog['status']['info'] = 'Соединение с БД недоступно';
	}

	$content['patterns']['list'] = $APP->catalog->patterns();
	
	$content['menu']['tools']['list'][1]['icon'] = "fa fa-wrench";
	$content['menu']['tools']['list'][1]['button'][1]['head'] = "Конфигурация справочников";
	$content['menu']['tools']['list'][1]['button'][1]['icon'] = "fa fa-cogs";
	$content['menu']['tools']['list'][1]['button'][1]['link'] = "admin/tools/codeeditor/?config=".$_ENV['facades']['path'].'/catalog.ini';


	$APP->template->file('admin/catalogs/list.html')->display($content);

