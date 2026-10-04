<?php

	$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);

	//Статика сообщений — компаньон-ini [view] + перевод
	$M = $APP->l10n->translate(array_replace_recursive([], (array) $APP->config->get()['view']))['messages'];


	if ($APP->objects->import($_POST['jsonData']))
	{
		echo "OK";
	}
	else
	{
		echo $M['fail']['text'];
	}

	exit();
