<?php

	/*
	 * Секция lint — php -l по всему коду платформы.
	 *
	 * 	./console test run lint
	 *
	 * Самый дешёвый и самый цепкий тест: синтаксис битого пакета
	 * ловится до любого рантайма. engine/vendor и vendor не трогаем —
	 * это чужие пакеты, не наш код.
	 */

	$php = PHP_SAPI === 'cli' ? PHP_BINARY : PHP_BINDIR.'/php';
	$bad = [];
	$count = 0;

	foreach (['engine/core', 'engine/facades', 'engine/console',
	          'app/controllers', 'app/models', 'app/services',
	          'index.php', 'console'] as $root)
	{
		if (!file_exists($root)) continue;
		if (is_file($root)) { $files = [$root]; }
		else
		{
			$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
			$files = [];
			foreach ($rii as $f)
				if ($f->isFile() and pathinfo($f, PATHINFO_EXTENSION) === 'php') $files[] = $f->getPathname();
		}
		foreach ($files as $file)
		{
			$count++;
			exec(escapeshellarg($php).' -l '.escapeshellarg($file).' 2>&1', $out, $code);
			if ($code !== 0)
			{
				$bad[$file] = trim(implode(' ', $out));
				echo "\t✗ $file\n";
			}
			$out = [];
		}
	}

	echo "\tпроверено файлов: $count\n";
	if ($bad) throw new Exception('Синтаксис сломан: '.implode(', ', array_keys($bad)));
