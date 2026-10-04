<?php

	$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);

	//Подгружаем конфигурацию
	$config = $APP->config->get();

	//Статическая GUI-структура — компаньон-ini, секция [view]
	$content = array_replace_recursive($content, (array) $config['view']);
	$content = $APP->l10n->translate($content);

	$content['catalogs']['list']  = $APP->catalog->listing();

	foreach ($content['catalogs']['list'] as $catalogName => &$_catalog)
	{
		$_catalog['icon'] = $_catalog['icon'] ?? 'fa fa-table';
		$_catalog['delete_confirm'] = sprintf($content['messages']['delete_confirm']['text'], $catalogName);

		//Контекстное меню каталога: статика из [view], динамика докручивается здесь
		$_catalog['context'] = $content['context'];

		$_catalog['context']['list']['impex']['list']['import']['catalog'] = $catalogName;
		$_catalog['context']['list']['impex']['list']['export']['catalog'] = $catalogName;

		$_catalog['context']['list']['config']['link'] = "admin/catalogs/config/edit?name=$catalogName";
		$_catalog['context']['list']['access']['link'] = "admin/catalogs/access/edit?name=$catalogName";

		$_catalog['context']['list']['delete']['name']    = $catalogName;
		$_catalog['context']['list']['delete']['confirm'] = $_catalog['delete_confirm'];

		if ($APP->db->connect($_catalog['db']) and ($_catalog['table']))
		{
			$_catalog['link']   = "admin/catalogs/view?name=$catalogName";
			$_catalog['status']['connect'] = 'active';
			continue;
		}
		//~ $_catalog['status']['icon'] = 'fa fa-plug';
		$_catalog['status']['icon'] = 'fa fa-low-vision';
		$_catalog['status']['tone'] = 'text-danger';
		$_catalog['status']['info'] = $content['status']['info'];
	}

	$content['patterns']['list'] = $APP->catalog->patterns();

	$content['menu']['tools']['list'][1]['icon'] = "fa fa-wrench";
	$content['menu']['tools']['list'][1]['button'][1] = $content['tools']['config'];
	$content['menu']['tools']['list'][1]['button'][1]['link'] = "admin/tools/codeeditor/?config=".$_ENV['facades']['app'].'/catalog.ini';


	$APP->template->file('admin/catalogs/list.html')->display($content);
