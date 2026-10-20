<?php

	/*
	 * Резервная копия платформы.
	 *
	 * 	./console backup [что копировать...]   — по умолчанию: app engine
	 *
	 * Копия кладётся в backups/<YYYY.MM.DD_HH-MM-SS>/<цель>.
	 * Каталог backups сам исключается (backup бэкапа не нужен).
	 */

	$targets = $args ?: ['app', 'engine'];
	$bakdir  = 'backups'.DIRECTORY_SEPARATOR.date("Y.m.d_H-i-s");

	foreach ($targets as $from)
	{
		if (!is_dir($from) and !is_file($from))
			throw new Exception("Backup target '$from' not found");

		if (!is_dir($bakdir) and !mkdir($bakdir, 0775, true))
			throw new Exception("Directory '$bakdir' creation denied");

		$count = backupCopy($from, $bakdir.DIRECTORY_SEPARATOR.$from);
		echo " $from → $count файл(ов)\n";
	}

	echo "Готово: $bakdir\n";


	//Рекурсивная копия файла/каталога (переносимо, без exec)
	function backupCopy($src, $dst)
	{
		if (is_file($src))
		{
			if (!copy($src, $dst)) throw new Exception("Copy failed: $src");
			return 1;
		}

		$count = 0;
		$rii = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($src, FilesystemIterator::SKIP_DOTS),
			RecursiveIteratorIterator::SELF_FIRST
		);
		foreach ($rii as $file)
		{
			$rel = substr($file->getPathname(), strlen($src));
			//сам себя не бэкапим
			if (trim($rel, DIRECTORY_SEPARATOR.'/') === 'backups') continue;

			$target = $dst.$rel;
			if ($file->isDir())
			{
				if (!is_dir($target) and !mkdir($target, 0755, true))
					throw new Exception("Directory '$target' creation denied");
				continue;
			}
			if (!is_dir(dirname($target))) mkdir(dirname($target), 0755, true);
			if (!copy($file->getPathname(), $target)) throw new Exception("Copy failed: {$file->getPathname()}");
			$count++;
		}
		return $count;
	}
