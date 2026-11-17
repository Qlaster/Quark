<?php

	$M = $APP->l10n->translate(array_replace_recursive([], (array) $APP->config->get()['view']))['messages'];

	$APP->user->policy->unban($_POST['ip']);
	echo $M['done'];
