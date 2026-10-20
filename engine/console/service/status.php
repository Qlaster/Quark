<?php

	/*
	 * Состояние службы.
	 *
	 * 	./console service status <name>
	 */

	$name = array_shift($args);
	if (!$name) throw new Exception("Использование: console service status <name>");

	$status = $APP->service->status($name);
	echo $status['running'] ? "running (pids: ".implode(', ', $status['pids']).")\n" : "stopped\n";
