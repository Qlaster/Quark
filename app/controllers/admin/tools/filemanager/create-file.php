<?php


	$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);

	//Статика сообщений — компаньон-ini [view] + перевод
	$M = $APP->l10n->translate(array_replace_recursive([], (array) $APP->config->get()['view']))['messages'];

	//~ echo $_GET['path'];
	//~ die;
	$f = $APP->files->jailPath(($_GET['path'] ?? '').DIRECTORY_SEPARATOR.($_GET['filename'] ?? ''));
	if (!$f or !touch($f))
	{
		exit($M['fail']['text']);
	}

	exit;
