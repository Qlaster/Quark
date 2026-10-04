<?php


	$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);

	//Статическая GUI-структура — компаньон-ini [view] + перевод
	$cfg = $APP->config->get();
	$content = array_replace_recursive($content, (array) $cfg['view']);
	$content = $APP->l10n->translate($content);

	$object = $APP->objects->collection($_GET['collection'])->get($_GET['object']);

	$collection = urlencode((string) $_GET['collection']);
	$objectname = urlencode((string) $_GET['object']);

	$content['config']['body']		= $APP->config->toString($object);
	$content['config']['action']	= "admin/constructor/object/plaintext/save?collection=$collection&object=$objectname";
	$content['config']['title'] 	= $content['blocks']['save']['head'];

	//~ $content['title'] = "Редактор объекта ".$_GET['object'];
	$content['nav']['path']['head'] = sprintf($content['navpath']['head'], $_GET['object']);

	$APP->template->file('admin/tools/code-editor/code-editor.html')->display($content);
