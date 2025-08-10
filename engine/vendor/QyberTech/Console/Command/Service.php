<?php

namespace QyberTech\Console\Command;

/*
 * Console Service
 *
 * Copyright 2025 vladimir
 *
 * 	console service start <serviceame>
 *
 *  console service list
 *
 *  console service status
 *
 */


class Service
{

	function start(...$args)
	{
		global $argv;
		$servicefile = $args[0][0];

		//Фальсифицируем массив $argv, что бы образение к сервису не воспринималось как запуск внутри фреймворка
		if ($_ENV['service']['limpid'])
		{
			$argv[0] = $argv[2];
			unset($argv[1], $argv[2]);
			$argv = array_values($argv);
		}

		$servicefile = $_ENV['service']['path'].DIRECTORY_SEPARATOR.$servicefile;
		$servicefile = is_file($servicefile.'.php') ? $servicefile.'.php' : $servicefile;

		if (!$servicefile or !is_readable($servicefile)) throw new \Exception("Service file not found or no access to file $servicefile");
		return require $servicefile;
	}

}
