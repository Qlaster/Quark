<?php

	/*
	 * Состояние policy-таблицы: счётчики, локи, баны.
	 *
	 * 	./console user policy [--json]
	 *
	 * Строки: (ip,login) — счётчик/лок пары; (ip,'') — бан адреса
	 * (until '9999-12-31' = перманент). --json — сырой вывод.
	 */

	$rows = $APP->user->policy->list();

	if (!empty($flags['json'])) { echo json_encode($rows, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT)."\n"; return; }

	if (!$rows) { echo "Пусто — ни локов, ни банов, ни счётчиков\n"; return; }

	$now = time();
	foreach ($rows as $r)
	{
		$state = '';
		if ($r['until'])
		{
			$left  = strtotime($r['until'].' UTC') - $now;
			$state = $r['login'] === ''
				? ($left > 1000*365*24*3600 ? 'БАН навсегда' : "БАН ещё {$left}с")
				: ($left > 0 ? "лок ещё {$left}с" : 'лок истёк');
		}
		echo str_pad($r['ip'], 16).' '.str_pad($r['login'] === '' ? '(адрес)' : $r['login'], 16)
			."count={$r['count']}".($state ? "  $state" : '')."\n";
	}
