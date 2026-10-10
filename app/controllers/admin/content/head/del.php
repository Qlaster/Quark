<?php


	$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);

	//Статика сообщений — компаньон-ini [view] + перевод
	$M = $APP->l10n->translate(array_replace_recursive([], (array) $APP->config->get()['view']))['messages'];

	//Якорь — дословная строка вставки
	$tag = trim($_POST['tag'] ?? '');
	if ($tag === '' or strlen($tag) > 200 or preg_match('/[\x00-\x1F\x7F]/', $tag))
	{
		http_response_code(400);
		echo $M['error']['text'];
		return;
	}

	$result = $APP->page->meta->delete(['type'=>'code','urlmask'=>'','name'=>$tag]) ? 'OK' : ($M['error']['text'] and http_response_code(500));

	echo $result;
