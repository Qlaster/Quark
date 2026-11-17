<?php

	$M = $APP->l10n->translate(array_replace_recursive([], (array) $APP->config->get()['view']))['messages'];

	$APP->user->policy->purge();
	echo $M['done'];
