<?php

	/*
	 * Запуск службы в текущем процессе.
	 *
	 * 	./console service run <name> [args...] [--force]
	 *
	 * Всё без '--' уходит службе в $argv; '--force' — флаг фасада.
	 * Файл ничего не печатает: вывод службы журналирует фасад
	 * (канал service.<name>), stdout под демоном глушится сам.
	 */

	$force = false;
	foreach ($args as $i => $a)
		if ($a === '--force') { $force = true; unset($args[$i]); }
	$args = array_values($args);

	$name = array_shift($args);
	if (!$name) throw new Exception("Использование: console service run <name> [args] [--force]");

	$APP->service->run($name, $args, ['force' => $force]);
