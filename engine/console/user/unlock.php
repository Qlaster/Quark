<?php

	/*
	 * Снятие лока/счётчика с пары или логина.
	 *
	 * 	./console user unlock <login> [ip]
	 *
	 * С ip — только пара; без — все записи логина.
	 * Удаляет строки целиком: и лок, и накопленный count.
	 * Маркеры бана (ip,'') не трогает — для бана user unban.
	 */

	$login = array_shift($args);
	$ip    = array_shift($args);

	if (!$login) throw new Exception("Использование: console user unlock <login> [ip]");

	$APP->user->policy->unlock($login, $ip);

	echo $ip ? "Записи '$ip:$login' сняты\n" : "Все записи логина '$login' сняты\n";
