<?php

	/*
	 * Справка консоли: ./console help
	 *
	 * Дерево команд engine/console/**.php + список фасадов.
	 * Сегменты '_*' — не команды, пропускаем.
	 */

	$dir = $this->config['console']['path'] ?? 'engine/console';

	echo "Quark console\n\n";
	echo "Использование: console <команда|фасад> [аргументы] [--флаги] [k=v]\n\n";

	echo "Команды ($dir):\n";
	$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
	foreach ($rii as $f)
	{
		$rel = substr($f->getPathname(), strlen($dir)+1);
		if ($rel === '' or $rel[0] === '_' or pathinfo($rel, PATHINFO_EXTENSION) !== 'php') continue;
		echo "\t".str_replace('/', ' ', substr($rel, 0, -4))."\n";
	}

	echo "\nФасады: ".implode(', ', array_keys($APP->__facades()))."\n";
