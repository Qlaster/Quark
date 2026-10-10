<?php


	$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);

	//Статическая структура страницы — компаньон index.ini, секция [view]
	$config  = $APP->config->get();
	$content = array_replace_recursive($content, (array) $config['view']);

	//Вкладки — фрагменты кода (type='code'), имя = тег привязки
	$content['tabs']['list'] = [];
	foreach ((array) $APP->page->meta->select(['type'=>'code','urlmask'=>'']) as $rec)
		$content['tabs']['list'][] = ['name' => $rec['name'], 'data' => $rec['data']];

	//Пусто — дефолтная вкладка </head>
	if (!$content['tabs']['list'])
		$content['tabs']['list'][] = ['name' => '</head>', 'data' => ''];

	//Локализация маркированных узлов
	$content = $APP->l10n->translate($content);

	$APP->template->file('admin/content/page.head.html')->display($content);

