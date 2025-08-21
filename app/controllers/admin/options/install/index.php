<?php


	$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);


	$installfile = 'app/install.ini';

	$content['config']['body'] 		= file_exists($installfile) ? file_get_contents($installfile) : '';
	$content['config']['action'] 	= "admin/options/install/save";
	$content['config']['filename']	= $installfile;
	$content['config']['title'] 	= 'Установить объекты';

	$content['title'] 				= $_GET['config'];


	if ($content['title'])
		$content['nav']['path']['head'] = $content['title'];

	$APP->template->file('admin/tools/code_editor/code_editor.html')->display($content);
