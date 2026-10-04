<?php


	$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);

	//Статическая структура страницы — компаньон index.ini, секция [view]
	$config  = $APP->config->get();
	$content = array_replace_recursive($content, (array) $config['view']);

	$content['code']['head'] = $APP->page->meta->select(['urlmask'=>'','name'=>'headcode'])[0]['data'] ?? '';

	//Локализация маркированных узлов
	$content = $APP->l10n->translate($content);

	$APP->template->file('admin/content/page.head.html')->display($content);

