<?php

	/*
	 * Лог последнего прогона: ./console test log [секция]
	 * Без имени — список секций с логами.
	 */

	$logdir = 'app/logs/tests';

	if (!($name = $args[0] ?? null))
	{
		foreach (glob("$logdir/*.log") ?: [] as $f)
			echo "\t".basename($f, '.log')."\n";
		return;
	}

	$file = "$logdir/$name.log";
	if (!is_file($file)) throw new Exception("Нет лога секции '$name' — ./console test run $name");
	readfile($file);
