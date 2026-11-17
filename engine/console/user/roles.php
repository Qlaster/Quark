<?php

	/*
	 * Список ролей (наборов прав доступа).
	 *
	 * 	./console user roles
	 *
	 * Роль — именованный набор denied-правил (см. user role).
	 */

	$roles = (array) $APP->user->role->get();

	if (!$roles) { echo "Ролей нет\n"; return; }

	foreach ($roles as $name => $rules)
	{
		echo "$name\n";
		foreach ((array) $rules as $path => $action)
			echo "\t$path → $action\n";
	}
