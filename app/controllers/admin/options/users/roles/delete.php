<?php

	//Статика сообщений — компаньон-ini [view] + перевод
	$M = $APP->l10n->translate(array_replace_recursive([], (array) $APP->config->get()['view']))['messages'];

	echo $APP->user->role->delete($_POST['name']) ? $M['ok']['text'] : $M['fail']['text'];
