<?php


	$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);

	//Статика сообщений — компаньон-ini [view] + перевод
	$M = $APP->l10n->translate(array_replace_recursive([], (array) $APP->config->get()['view']))['messages'];

	$result = $APP->page->meta->replace(['urlmask'=>'','name'=>'headcode','data'=>$_POST['headcode']]) ? 'OK' : ($M['error']['text'] and http_response_code(500));

	echo $result;

