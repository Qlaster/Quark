<?php

	/*
	 * Переименование роли.
	 *
	 * 	./console user roles rename <old> <new>
	 */

	$old = array_shift($args);
	$new = array_shift($args);

	if (!$old || !$new) throw new Exception("Использование: console user roles rename <old> <new>");

	if ($APP->user->role->get($old) === null)
		throw new Exception("Роли '$old' нет");
	if ($APP->user->role->get($new) !== null)
		throw new Exception("Роль '$new' уже существует");

	$APP->user->role->rename($old, $new);
	echo "Роль '$old' → '$new'\n";
