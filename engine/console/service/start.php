<?php

	/*
	 * Запуск службы демоном в фоне.
	 *
	 * 	./console service start <name> [--force]
	 */

	$force = false;
	foreach ($args as $i => $a)
		if ($a === '--force') { $force = true; unset($args[$i]); }
	$args = array_values($args);

	$name = array_shift($args);
	if (!$name) throw new Exception("Использование: console service start <name> [--force]");

	$status = $APP->service->start($name, $args, ['force' => $force]);
	echo $status['running'] ? "Служба '$name' запущена (pids: ".implode(', ', $status['pids']).")\n"
	                        : "Служба '$name' не поднялась — смотри журнал\n";
