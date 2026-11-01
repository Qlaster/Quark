<?php


	$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);

	//Статика сообщений — компаньон-ini [view] + перевод
	$M = $APP->l10n->translate(array_replace_recursive([], (array) $APP->config->get()['view']))['messages'];

	//Получаем входные параметры
	$collection	= urldecode($_GET['collection']);
	$objectname	= urldecode($_GET['objectname']);
	$lang		= urldecode($_GET['lang'] ?? '');

	if (!$objectname)
	{
		http_response_code(400);
		exit($M['noobject']['text']);
	}


	if ($_POST['object'])
	{
		$object = (array) json_decode($_POST['object'], true);

		if (!$APP->object->collection($collection)->lang($lang)->set($objectname, $object))
		{
			http_response_code(400);
			exit($M['fail']['text']);
		}
	}
