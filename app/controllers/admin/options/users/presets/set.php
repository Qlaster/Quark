<?php

	//Статика сообщений — компаньон-ini [view] + перевод
	$M = $APP->l10n->translate(array_replace_recursive([], (array) $APP->config->get()['view']))['messages'];

	echo $APP->user->preset->set($_GET['name'], $_POST['denied']) ? $M['ok']['text'] : $M['fail']['text'];
