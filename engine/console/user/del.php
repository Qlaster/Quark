<?php

	/*
	 * Удаление пользователя.
	 *
	 * 	./console user del <login> [--yes]
	 *
	 * Без --yes на терминале спросит подтверждение.
	 */

	$login = array_shift($args);

	if (!$login) throw new Exception("Использование: console user del <login> [--yes]");

	if (! $APP->user->exists($login))
		throw new Exception("Пользователя '$login' нет");

	if (empty($flags['yes']) and function_exists('posix_isatty') and posix_isatty(STDIN))
	{
		echo "Удалить '$login'? [y/N] ";
		if (mb_strtolower(trim(fgets(STDIN))) !== 'y') { echo "Отменено\n"; return; }
	}

	$APP->user->del($login);
	echo "Пользователь '$login' удалён\n";
