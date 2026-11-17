<?php

	$M = $APP->l10n->translate(array_replace_recursive([], (array) $APP->config->get()['view']))['messages'];

	$APP->user->policy->unlock($_POST['login'], $_POST['ip'] ?: null);
	echo $M['done'];
