<?php

	$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);

	//Статическая GUI-структура — компаньон-ini [view] + перевод
	$cfg = $APP->config->get();
	$content = array_replace_recursive($content, (array) $cfg['view']);
	$content = $APP->l10n->translate($content);

	$content['object'] = $_GET['object'];

	//Шаблон ждёт <title> скаляром — узел сплющиваем до head
	$content['title'] = sprintf($content['title']['head'], $_GET['object']);

	$APP->template->file('admin/constructor/object/object.edit.html')->display($content);
