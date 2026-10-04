<?php

	//Статика сообщений — компаньон-ini [view] + перевод
	$M = $APP->l10n->translate(array_replace_recursive([], (array) $APP->config->get()['view']))['messages'];

	try
	{
		$object = $APP->config->fromString($_POST['config']['body']);
	}
	catch (Error $e)
	{
		echo $e->getMessage();
		exit;
	}

	echo ($APP->objects->collection($_GET['collection'])->set($_GET['object'], $object)) ? $M['saved']['text'] : $M['fail']['text'];



