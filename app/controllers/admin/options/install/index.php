<?php


	$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);

	//~ $content['title'] = 'Редактор кода';
	//~ print_r($content); die;


	//Файлы, который нужно открыть
	//~ if (file_exists($_GET['file'] ?? ''))
	//~ {
		//~ $content['code']['body'] 		= file_get_contents($_GET['file']);
		//~ $content['code']['action'] 		= "admin/tools/codeeditor/save.php";
		//~ $content['code']['filename']	= $_GET['file'];
		//~ $content['title'] 				= $_GET['file'];
	//~ }
	//~ if (file_exists($_GET['config'] ?? ''))
	//~ {
		$installfile = 'app/install.ini';

		$content['config']['body'] 		= file_exists($installfile) ? file_get_contents($installfile) : '';
		$content['config']['action'] 	= "admin/options/install/save";
		$content['config']['filename']	= $installfile;
		$content['config']['title'] 	= 'Установить объекты';

		$content['title'] 				= $_GET['config'];
	//~ }

	if ($content['title'])
		$content['nav']['path']['head'] = $content['title'];



	//~ $content['code'] = file_get_contents('engine/lib/view/QTemplate.php');
	//~ $content['config'] = file_get_contents('engine/units/route.ini');
	//~ $themelink = $APP->url->home()."views/admin/";

	$APP->template->file('admin/tools/code_editor/code_editor.html')->display($content);
