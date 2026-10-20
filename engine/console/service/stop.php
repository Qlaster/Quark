<?php

	/*
	 * Остановка службы (всех её процессов или одного pid).
	 *
	 * 	./console service stop <name> [pid]
	 */

	$name = array_shift($args);
	if (!$name) throw new Exception("Использование: console service stop <name> [pid]");

	$result = $APP->service->stop($name, $args[0] ?? null);
	echo "stopped: ".($result['stopped'] ? implode(', ', $result['stopped']) : '—')."\n";
	if ($result['running']) echo "running: ".implode(', ', $result['running'])."\n";
