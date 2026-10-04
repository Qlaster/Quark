<?php

	$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);

	//Статическая GUI-структура — компаньон-ini [view] + перевод
	$cfg = $APP->config->get();
	$content = array_replace_recursive($content, (array) $cfg['view']);
	$content = $APP->l10n->translate($content);

	$content['title'] = $content['journal']['head'];

	//Если не передали даты - установим сегодняшний день.
	//Записи подтягивает DataTables через journal/data (serverSide)
	$content['datestart'] = $_GET['datestart'] ? $_GET['datestart'] : date('d.m.Y');
	$content['dateend']   = $_GET['dateend']   ? $_GET['dateend']   : date('d.m.Y');
	$content['errors']    = $_GET['errors'];

	$APP->template->file('admin/tools/journal/journal.html')->display($content);
