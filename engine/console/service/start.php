<?php

	/*
	 * Запуск службы демоном в фоне.
	 *
	 * 	./console service start <name> [args...] [--force]
	 *
	 * args/k=v/--flags уходят службе в $argv как были (см. service run);
	 * '--force' фасад трактует сам, но в argv он тоже виден.
	 */

	$name = array_shift($args);
	if (!$name) throw new Exception("Использование: console service start <name> [args] [--force]");

	//Как в run.php: собираем токены обратно в argv службы — k=v и
	//--flag/--k=v, включая '--force'.
	foreach ($vars  as $k => $v) $args[] = "$k=$v";
	foreach ($flags as $k => $v) $args[] = $v === true ? "--$k" : "--$k=$v";

	$status = $APP->service->start($name, $args, ['force' => !empty($flags['force'])]);
	echo $status['running'] ? "Служба '$name' запущена (pids: ".implode(', ', $status['pids']).")\n"
	                        : "Служба '$name' не поднялась — смотри журнал\n";
