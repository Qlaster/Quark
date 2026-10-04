<?php

	error_reporting(E_ALL & ~E_NOTICE);


	$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);

	//Статическая GUI-структура — компаньон-ini [view] + перевод
	$cfg = $APP->config->get();
	$content = array_replace_recursive($content, (array) $cfg['view']);
	$content = $APP->l10n->translate($content);

	$base  = $_GET['base'];
	$table = $_GET['table'];
	$content['table']['data']['name']	= $_GET['base'];


	$content['catalog']['types'] = $APP->db->config['patterns'];


	$APP->template->file('admin/dbmanager/db_construct.html')->display($content);

