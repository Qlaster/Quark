<?php

	/*
	 * Создание пользователя.
	 *
	 * 	./console user add <login> [password] [--name=.. --email=.. --info=.. --disable=0|1 --поле=..]
	 *
	 * Пароль не задан — спросим интерактивно (без эха), а если вход
	 * не терминал — прочтём первую строку stdin.
	 * Любые прочие --k=v уходят пользовательскими полями в data.
	 */

	$login    = array_shift($args);
	$password = array_shift($args);

	if (!$login) throw new Exception("Использование: console user add <login> [password] [--name=.. --email=..]");

	if ($APP->user->exists($login))
		throw new Exception("Пользователь '$login' уже существует");

	//Пароль: аргумент → интерактив → stdin
	if ($password === null)
	{
		if (function_exists('posix_isatty') and posix_isatty(STDIN))
		{
			echo "Password: ";
			system('stty -echo');
			$password = trim(fgets(STDIN));
			system('stty echo');
			echo "\n";
		}
		else $password = trim(fgets(STDIN));
	}

	if ($password == '') throw new Exception("Пароль не задан");

	//Флаги — поля пользователя (системные уйдут колонками, прочие — в data)
	$user = ['login'=>$login, 'password'=>$password] + $flags;

	if (! $APP->user->add($user))
		throw new Exception("Не удалось создать '$login' (политика пароля? см. [policy] password.*)");

	echo "Пользователь '$login' создан\n";
