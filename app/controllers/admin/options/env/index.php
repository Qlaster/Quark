<?php


	$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);

	//Статическая GUI-структура — компаньон-ini [view] + перевод
	$cfg = $APP->config->get();
	$content = array_replace_recursive($content, (array) $cfg['view']);
	$content = $APP->l10n->translate($content);


	$envFile = $_GET['file'] ?? '.env';
	$envPath = $APP->files->jailPath($envFile);

	$content['config']['body'] 		= ($envPath and is_readable($envPath)) ? file_get_contents($envPath) : '';
	$content['config']['action'] 	= "admin/options/env/save";
	$content['config']['filename']	= $envFile;
	$content['config']['title'] 	= $content['blocks']['save']['head'];

	$content['title'] 				= $_GET['config'] ?: $content['title']['head'];


	$content['menu']['config']['list']['app/.env']['head'] = $content['envlist']['app'];
	$content['menu']['config']['list']['app/.env']['link'] = 'admin/options/env/?file=app/.env';
	$content['menu']['config']['list']['.env']['head']     = $content['envlist']['core'];
	$content['menu']['config']['list']['.env']['link']     = 'admin/options/env/?file=.env';
	$content['menu']['config']['list'][$envFile]['active'] = 'active';

	if ($content['title'])
		$content['nav']['path']['head'] = $content['title'];

	$APP->template->file('admin/tools/code-editor/code-editor.html')->display($content);
