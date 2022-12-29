<?php


	$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);

	$content['title'] = 'Редактор кода';

	//Файлы, который нужно открыть
	if (file_exists($_GET['file']))
	{
		$content['code']['body'] 		= file_get_contents($_GET['file']);
		$content['code']['action'] 		= "admin/app/codeeditor/save.php";
		$content['code']['filename']	= $_GET['file'];
		$content['title'] 				= $_GET['file'];
	}
	if (file_exists($_GET['config']))
	{
		$content['config']['body'] 		= file_get_contents($_GET['config']);
		$content['config']['action'] 	= "admin/app/codeeditor/save.php";
		$content['config']['filename']	= $_GET['config'];
		$content['title'] 				= $_GET['config'];
	}




	//~ $content['code'] = file_get_contents('engine/lib/view/QTemplate.php');
	//~ $content['config'] = file_get_contents('engine/units/route.ini');
	//~ $themelink = $APP->url->home()."views/admin/";

	$APP->template->file('admin/app/code_editor/code_editor.html')->display($content);
