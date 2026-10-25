<?php

	/*
	 * Список тестовых секций: ./console test list
	 */

	$dir = ($this->config['console']['path'] ?? 'engine/console').'/test/run';

	foreach (glob("$dir/*.php") ?: [] as $f)
		echo "\t".basename($f, '.php')."\n";
