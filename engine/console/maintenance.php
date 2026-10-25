<?php

	/*
	 * Режим обслуживания: ./console maintenance on|off
	 *
	 * Переключает enabled в .env[maintenance] — index.php чекает
	 * флаг до бутстрапа и отдаёт контроллер maintenance.controller.
	 * Запись точечная: меняется только значение enabled, остальное
	 * в .env не трогаем. Ключ 'on' — резерв ini-грамматики, не годится.
	 */

	$state = $args[0] ?? null;
	if (!in_array($state, ['on','off'])) throw new Exception("Использование: console maintenance on|off");

	$val = $state === 'on' ? '1' : '0';
	$env = file_get_contents('.env');

	//enabled внутри секции [maintenance] (лениво — до следующей [секции])
	$rx = '/(\[maintenance\][^\[]*?enabled[ \t]*=[ \t]*)\S+/s';
	$env = preg_match($rx, $env)
		? preg_replace($rx, "\${1}$val", $env)
		: $env."\n[maintenance]\nenabled = $val\n";

	file_put_contents('.env', $env);
	echo $state === 'on' ? "Режим обслуживания: ВКЛ (сайт отвечает 503)\n" : "Режим обслуживания: ВЫКЛ\n";
