<?php


	$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);


	$envFile = $_GET['file'] ?? '.env';

	$content['config']['body'] 		= is_readable($envFile) ? file_get_contents($envFile) : '';
	$content['config']['action'] 	= "admin/options/env/save";
	$content['config']['filename']	= $envFile;
	$content['config']['title'] 	= 'Установить объекты';

	$content['title'] 				= $_GET['config'];

	$content['menu']['config']['list']['.env']['head']     = 'ENV платформы';
	$content['menu']['config']['list']['.env']['link']     = 'admin/options/env/?file=.env';
	$content['menu']['config']['list']['app/.env']['head'] = 'ENV приложения';
	$content['menu']['config']['list']['app/.env']['link'] = 'admin/options/env/?file=app/.env';
	$content['menu']['config']['list'][$envFile]['active'] = 'active';

	if ($content['title'])
		$content['nav']['path']['head'] = $content['title'];

	$APP->template->file('admin/tools/code-editor/code-editor.html')->display($content);
