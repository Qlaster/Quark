<?php


	$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);

	//Статическая GUI-структура — компаньон-ini [view] + перевод
	$cfg = $APP->config->get();
	$content = array_replace_recursive($content, (array) $cfg['view']);
	$content = $APP->l10n->translate($content);

	//~ $content['title'] = 'Редактор кода';
	//~ print_r($content); die;


	//Шаблон ждёт <title> скаляром — дефолт из узла, файл/конфиг перекроют
	$content['title'] = $content['title']['head'];

	//Файлы, который нужно открыть (только внутри разрешённых директорий)
	if (($f = $APP->files->jailPath($_GET['file'] ?? '')) and is_file($f))
	{
		$content['code']['body'] 		= file_get_contents($f);
		$content['code']['action'] 		= "admin/tools/codeeditor/save";
		$content['code']['filename']	= $f;
		$content['title'] 				= $_GET['file'];
	}
	if (($f = $APP->files->jailPath($_GET['config'] ?? '')) and is_file($f))
	{
		$content['config']['body'] 		= file_get_contents($f);
		$content['config']['action'] 	= "admin/tools/codeeditor/save";
		$content['config']['filename']	= $f;
		$content['config']['title'] 	= $content['blocks']['save']['head'];
		$content['title'] 				= $_GET['config'];
	}

	if ($content['title'])
		$content['nav']['path']['head'] = $content['title'];



	//~ $content['code'] = file_get_contents('engine/lib/view/QTemplate.php');
	//~ $content['config'] = file_get_contents('engine/units/route.ini');
	//~ $themelink = $APP->url->home()."views/admin/";

	$APP->template->file('admin/tools/code-editor/code-editor.html')->display($content);
