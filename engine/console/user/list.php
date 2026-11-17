<?php

	/*
	 * Список учёток админ-панели.
	 *
	 * 	./console user list [--json]
	 *
	 * Колонки: login, name, email, disable. --json — сырой массив.
	 */

	$users = $APP->user->all();

	if (!empty($flags['json'])) { echo json_encode($users, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT)."\n"; return; }

	if (!$users) { echo "Пользователей нет\n"; return; }

	//ширина колонок по содержимому
	$w = ['login'=>5, 'name'=>4, 'email'=>5];
	foreach ($users as $u)
	{
		$w['login'] = max($w['login'], mb_strlen($u['login']));
		$w['name']  = max($w['name'],  mb_strlen($u['name']));
		$w['email'] = max($w['email'], mb_strlen($u['email']));
	}

	printf("%-{$w['login']}s  %-{$w['name']}s  %-{$w['email']}s  disable\n", 'login','name','email');
	foreach ($users as $u)
		printf("%-{$w['login']}s  %-{$w['name']}s  %-{$w['email']}s  %d\n",
			$u['login'], $u['name'], $u['email'], (int) $u['disable']);
