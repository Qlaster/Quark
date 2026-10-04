<?php


	$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);

	//Статика сообщений — компаньон-ini [view] + перевод
	$M = $APP->l10n->translate(array_replace_recursive([], (array) $APP->config->get()['view']))['messages'];

	$dir = $APP->files->jailPath(($_GET['path'] ?? '').DIRECTORY_SEPARATOR.($_GET['filename'] ?? ''));
	if (!$dir or !mkdir($dir, 0777, true))
	{
		die($M['fail']['text']);
	}

	die;
