<?php
    
    $content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);

	//Статика сообщений — компаньон-ini [view] + перевод
	$M = $APP->l10n->translate(array_replace_recursive([], (array) $APP->config->get()['view']))['messages'];

	$collection	= $_POST['collection'];
	$nameA	= $_POST['object'] ? $_POST['object'] : $_POST['new_name'];
	$nameB = $_POST['object'] ? $_POST['new_name'] : null;

	if (!$collection || !$nameA)
	{
		http_response_code(400);
		exit($M['nocollect']['text']);
	}

	if (!$APP->object->collection($collection)->rename($nameA, $nameB))
	{
		http_response_code(400);
		exit($M['fail']['text']);
	}

	header('Location: index' . ($_POST['object'] ? '?collection='.$collection : ''));