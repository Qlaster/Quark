<?php

	/*
	 * Установка свойств пользователя.
	 *
	 * 	./console user edit <login> [--name=.. --email=.. --info=.. --disable=0|1 --любое=поле]
	 *
	 * Флаги пишутся в запись: известные — в колонки, прочие — в data.
	 * Пароль тут не меняем — для этого user password.
	 */

	$login = array_shift($args);

	if (!$login)  throw new Exception("Использование: console user edit <login> [--name=.. --email=.. --disable=0|1]");
	if (!$flags)  throw new Exception("Нечего менять — передай --k=v");

	//Грузим запись и мержим правки — edit() требует заполненный hash
	$user = $APP->user->get($login);
	if (! $user) throw new Exception("Пользователя '$login' нет");

	$user = array_merge($user, $flags);

	if (! $APP->user->edit($user))
		throw new Exception("Не удалось обновить '$login'");

	echo "Пользователь '$login' обновлён\n";
