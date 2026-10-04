<?php

	$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);

	//Статика сообщений — компаньон-ini [view] + перевод
	$M = $APP->l10n->translate(array_replace_recursive([], (array) $APP->config->get()['view']))['messages'];

	try
	{
		//jail-проверка пути выполняется внутри remove() (см. files.ini [jail])
		$result = $APP->files->remove($_GET['path']);
	}
	catch (Exception $e)
	{
		echo $M['fail']['text'],  $e->getMessage(), "\n";
	}


	exit;
