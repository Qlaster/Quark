<?php

	/*
	 * Секция db — живость всех объявленных соединений.
	 *
	 * 	./console test run db
	 *
	 * Каждый [connect] из db.ini + app/db.d/*.ini: connect() → tables().
	 * Ловит битые файлы sqlite, кривые DSN, права на чтение.
	 */

	$failed = [];
	foreach (array_keys((array) $APP->db->config['connect']) as $name)
	{
		$orm = $APP->db->connect($name);
		if (!$orm)
		{
			$failed[$name] = 'connect() вернул false';
			echo "\t✗ $name — соединение не поднялось\n";
			continue;
		}
		try
		{
			$tables = $orm->tables();
			echo "\t✓ $name (таблиц: ".count((array) $tables).")\n";
		}
		catch (Throwable $e)
		{
			$failed[$name] = $e->getMessage();
			echo "\t✗ $name — ".$e->getMessage()."\n";
		}
	}

	if ($failed) throw new Exception('Мёртвые соединения: '.implode(', ', array_keys($failed)));
