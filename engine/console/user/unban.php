<?php

	/*
	 * Снятие бана с ip-адреса.
	 *
	 * 	./console user unban <ip>
	 *
	 * Удаляет маркер (ip,'') и ВСЕ пары адреса — иначе суммарный
	 * счётчик пересоздал бы бан на следующем запросе.
	 */

	$ip = array_shift($args);

	if (!$ip) throw new Exception("Использование: console user unban <ip>");

	$APP->user->policy->unban($ip);
	echo "Адрес '$ip' разбанен, счётчики очищены\n";
