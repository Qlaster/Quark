<?php

	/*
	 * Смена пароля пользователя.
	 *
	 * 	./console user password <login> [новый_пароль]
	 *
	 * Пароль не задан — спросим интерактивно (без эха), а если вход
	 * не терминал — прочтём первую строку stdin.
	 * Аварийный сценарий: работает даже для заблокированной/забаненного пользователя —
	 * счётчики policy тут не участвуют.
	 */

	$login    = array_shift($args);
	$password = array_shift($args);

	if (!$login) throw new Exception("Использование: console user password <login> [пароль]");

	$user = $APP->user->get($login);
	if (! $user) throw new Exception("Пользователя '$login' нет");

	if ($password === null)
	{
		if (function_exists('posix_isatty') and posix_isatty(STDIN))
		{
			echo "New password: ";
			system('stty -echo');
			$password = trim(fgets(STDIN));
			system('stty echo');
			echo "\n";
		}
		else $password = trim(fgets(STDIN));
	}

	if ($password == '') throw new Exception("Пароль не задан");

	$user['password'] = $password;

	if (! $APP->user->edit($user))
		throw new Exception("Не удалось сменить пароль (политика? см. [policy] password.*)");

	echo "Пароль '$login' обновлён\n";
