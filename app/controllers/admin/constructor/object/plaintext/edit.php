<?php


	$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);

	$object = $APP->objects->collection($_GET['collection'])->get($_GET['object']);

	$collection = urlencode($_GET['collection']);
	$objectname = urlencode($_GET['object']);

	$content['config']['body']		= $APP->config->toString($object);
	$content['config']['action']	= "admin/constructor/object/plaintext/save?collection=$collection&object=$objectname";

	//~ $content['title'] = "Редактор объекта ".$_GET['object'];
	$content['nav']['path']['head'] = "Редактор объекта [".$_GET['object']."]";

	$APP->template->file('admin/app/code_editor/code_editor.html')->display($content);
