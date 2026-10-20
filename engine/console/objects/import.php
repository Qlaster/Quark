<?php

	/*
	 * Импорт объектов в БД (коллекции objects).
	 *
	 * Использование:
	 * 	./console objects import <файл...> [--strict|--skip]
	 *
	 * Форматы файлов:
	 * 	.json — строка в формате objects->export()
	 * 	.ini  — ini-грамматика [collection] name.field = "..."
	 * Формат определяется по содержимому (is_json), не по расширению.
	 * Файлов можно несколько — импортируются по порядку.
	 *
	 * Политика коллизий (документ существует в БД):
	 * 	по умолчанию — перезапись молча (легаси-поведение)
	 * 	--strict     — есть коллизии → исключение ДО записи,
	 * 	               база остаётся нетронутой (двухфазно)
	 * 	--skip       — существующие пропускаются, новые заливаются
	 *
	 * Примеры:
	 * 	./console objects import dump.json
	 * 	./console objects import install.ini menu.ini
	 * 	./console objects import dump.json --strict
	 * 	./console objects import dump.ini --skip
	 *
	 * После импорта печатается отчёт: [коллекция] и список документов.
	 * Провал файла/парсинга — исключение (stderr, exit≠0).
	 */

	$flags = 0;
	$files = [];
	foreach ($args as $a)
	{
		if     ($a === '--strict') $flags |= $APP->objects::IMPORT_STRICT;
		elseif ($a === '--skip')   $flags |= $APP->objects::IMPORT_SKIP;
		else   $files[] = $a;
	}
	if (!$files) throw new Exception("Использование: console objects import <файл...> [--strict|--skip]");

	foreach ($files as $filename)
	{
		if (!is_file($filename) or !is_readable($filename))
			throw new Exception("File '$filename' not found or not readable");

		$source = file_get_contents($filename);
		if (is_json($source))
			$json = $source;
		elseif (($collections = $APP->config->get($filename)) and is_array($collections))
			$json = json_encode($collections);
		else
			throw new Exception("Error parse file: '$filename'");

		$APP->objects->import($json);

		//отчёт — что реально легло
		foreach (json_decode($json, true) as $section => $objects)
		{
			echo "\n[$section]";
			foreach (array_keys((array) $objects) as $name)
				echo "\n\t| $name";
		}
		echo "\n";
	}
