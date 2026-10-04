<?php


	$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);

	//Статическая GUI-структура — компаньон-ini [view] + перевод
	$cfg = $APP->config->get();
	$content = array_replace_recursive($content, (array) $cfg['view']);
	$content = $APP->l10n->translate($content);


	$installfile = 'app/install.ini';

	$content['config']['body'] 		= file_exists($installfile) ? file_get_contents($installfile) : '';
	$content['config']['action'] 	= "admin/options/install/save";
	$content['config']['filename']	= $installfile;
	$content['config']['title'] 	= $content['install']['button']['head'];

	$content['title'] 				= $_GET['config'] ?: $content['title']['head'];


	if ($content['title'])
		$content['nav']['path']['head'] = $content['title'];

	$APP->template->file('admin/tools/code-editor/code-editor.html')->display($content);
