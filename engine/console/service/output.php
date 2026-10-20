<?php

	/*
	 * Вывод службы (журнал). Число — хвост строк, без числа — целиком.
	 *
	 * 	./console service output <name> [lines]
	 */

	$name = array_shift($args);
	if (!$name) throw new Exception("Использование: console service output <name> [lines]");

	$data = $APP->service->output($name, isset($args[0]) ? (int) $args[0] : null);
	if ($data !== null) echo rtrim($data, "\n"), "\n";
