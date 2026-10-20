<?php

	/*
	 * Экспорт объектов из БД (коллекции objects).
	 *
	 * Использование:
	 * 	./console objects export [файл] [--collection=<имя>]
	 *
	 * Без файла — JSON в stdout, удобно в пайп:
	 * 	./console objects export > dump.json
	 * 	./console objects export | grep mainmenu
	 *
	 * Формат файла определяется расширением:
	 * 	.ini    — ini-грамматика [collection] name.field = "..."
	 * 	прочее  — сырой JSON
	 *
	 * 	--collection=<имя> — выгрузить только одну коллекцию
	 *
	 * Примеры:
	 * 	./console objects export dump.json
	 * 	./console objects export dump.ini
	 * 	./console objects export menu.json --collection=admin
	 *
	 * После записи печатается отчёт: [коллекция] и список документов.
	 */

	$file = null;
	$coll = null;
	foreach ($args as $a)
	{
		if (strpos($a, '--collection=') === 0) $coll = substr($a, 13);
		else $file = $a;
	}

	//конкретная коллекция или все объекты системы
	$json = $coll ? $APP->objects->collection($coll)->export() : $APP->objects->export();

	if (!$file)
	{
		echo $json, "\n";
		exit;
	}

	if (pathinfo($file, PATHINFO_EXTENSION) === 'ini')
		$APP->config->set(json_decode($json, true), $file);
	else
		file_put_contents($file, $json);

	if (!is_readable($file)) throw new Exception("Error export file '$file' — не пишется");

	//отчёт — что выгрузили
	foreach (json_decode($json, true) as $section => $objects)
	{
		echo "\n[$section]";
		foreach (array_keys((array) $objects) as $name)
			echo "\n\t| $name";
	}
	echo "\n\n→ $file\n";
