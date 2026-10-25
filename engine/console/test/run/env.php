<?php

	/*
	 * Секция env — минимальная целостность конфигурации.
	 *
	 * 	./console test run env
	 *
	 * Обязательные секции .env на месте, пути из них существуют.
	 * Ловит битый merge конфигурации (обновление, ручная правка).
	 */

	$need = ['core', 'vendor', 'facades'];
	foreach ($need as $sec)
		if (empty($_ENV[$sec])) throw new Exception("В .env нет секции [$sec]");

	//пути из конфигурации должны существовать
	foreach (['core' => 'path', 'vendor' => 'engine'] as $sec => $key)
		if ($p = $_ENV[$sec][$key] ?? null and !file_exists($p))
			throw new Exception("Путь из .env[$sec].$key не существует: $p");

	//core.php доступен
	if (!is_file($_ENV['core']['path'].'core.php'))
		throw new Exception('Не найден '.$_ENV['core']['path'].'core.php');

	echo "\t✓ секции на месте, пути существуют\n";
