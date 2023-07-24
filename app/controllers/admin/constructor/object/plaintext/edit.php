<?php


	$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);

	$object = $APP->objects->collection($_GET['collection'])->get($_GET['object']);

	$collection = urlencode((string) $_GET['collection']);
	$objectname = urlencode((string) $_GET['object']);

	$content['config']['body']		= $APP->config->toString($object);
	$content['config']['action']	= "admin/constructor/object/plaintext/save?collection=$collection&object=$objectname";
	$content['config']['title'] 	= 'Сохранить';

	//~ $content['title'] = "Редактор объекта ".$_GET['object'];
	$content['nav']['path']['head'] = "Редактор объекта [".$_GET['object']."]";

	$APP->template->file('admin/tools/code_editor/code_editor.html')->display($content);
