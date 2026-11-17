<?php

	/*
	 * Удаление роли.
	 *
	 * 	./console user roles del <name> [--yes]
	 *
	 * Пользователей с назначенной ролью не трогает — правила скопированы.
	 * Без --yes на терминале спросит подтверждение.
	 */

	$name = array_shift($args);

	if (!$name) throw new Exception("Использование: console user roles del <name> [--yes]");

	if ($APP->user->role->get($name) === null)
		throw new Exception("Роли '$name' нет");

	if (empty($flags['yes']) and function_exists('posix_isatty') and posix_isatty(STDIN))
	{
		echo "Удалить роль '$name'? [y/N] ";
		if (mb_strtolower(trim(fgets(STDIN))) !== 'y') { echo "Отменено\n"; return; }
	}

	$APP->user->role->delete($name);
	echo "Роль '$name' удалена\n";
