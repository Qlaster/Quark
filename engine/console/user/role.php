<?php

	/*
	 * Назначение роли пользователю.
	 *
	 * 	./console user role <login> <name>
	 *
	 * ЗАМЕНЯЕТ denied-правила пользователя правилами роли —
	 * как выбор роли в GUI. Правила не мержатся: что было — стёрто.
	 */

	$login = array_shift($args);
	$role  = array_shift($args);

	if (!$login || !$role) throw new Exception("Использование: console user role <login> <name>");

	$user = $APP->user->get($login);
	if (! $user) throw new Exception("Пользователя '$login' нет");

	$rules = $APP->user->role->get($role);
	if ($rules === null)
		throw new Exception("Роли '$role' нет — список: console user roles");

	$user['denied'] = $rules;

	if (! $APP->user->edit($user))
		throw new Exception("Не удалось назначить роль '$role'");

	echo "Роль '$role' назначена пользователю '$login'\n";
