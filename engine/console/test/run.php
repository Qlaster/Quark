<?php

	/*
	 * Прогон всех тестовых секций.
	 *
	 * 	./console test run           — все секции test/run/*.php
	 * 	./console test run facades   — одна секция (резолвит диспетчер,
	 * 	                               run/facades.php — сама команда)
	 *
	 * Сюда доходим только с пустым хвостом: именованная секция
	 * вызывается напрямую как команда. Здесь — общий прогон:
	 * каждая секция редиспетчится, вывод ловится и пишется в
	 * app/logs/tests/<name>.log. Exit≠0 если хоть одна упала —
	 * ворота для console update.
	 */

	$dir    = ($this->config['console']['path'] ?? 'engine/console').'/test/run';
	$logdir = 'app/logs/tests';
	is_dir($logdir) or mkdir($logdir, 0775, true);

	$sections = array_map(function ($f) { return basename($f, '.php'); }, glob("$dir/*.php") ?: []);
	if (!$sections) { echo "Нет тестовых секций ($dir)\n"; return; }

	$failed = [];
	foreach ($sections as $section)
	{
		echo "── $section ".str_repeat('─', max(1, 50-strlen($section)))."\n";

		ob_start();
		try
		{
			$this->dispatch(['test', 'run', $section]);
			$out = ob_get_clean();
		}
		catch (Throwable $e)
		{
			$out = ob_get_clean()."✗ ".$e->getMessage()."\n";
			$failed[$section] = $e->getMessage();
		}

		echo $out;
		//лог прогона секции — перезаписываем (последний run актуален)
		file_put_contents("$logdir/$section.log",
			"# ".date('Y-m-d H:i:s')." — ".(isset($failed[$section]) ? "FAIL" : "OK")."\n".$out);
	}

	echo "\n";
	if ($failed)
	{
		echo "ПРОВАЛ: ".implode(', ', array_keys($failed))."\n";
		exit(1);
	}
	echo "Все секции зелёные (".implode(', ', $sections).")\n";
