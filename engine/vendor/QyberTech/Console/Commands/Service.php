<?php

namespace QyberTech\Console\Commands;

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

	function list()
	{
		foreach (glob($_ENV['service']['path'].DIRECTORY_SEPARATOR.'*.php') as $fileName)
		{
			$size = filesize($fileName);
			$base = basename($fileName);
			$name = pathinfo($fileName)['filename'];
			$modifiedTime = filemtime($fileName);
			$modifiedTime = date("Y-m-d H:i:s", $modifiedTime);

			$start = '⚪';
			$stop = '⚫';
			$hide = '●';

			echo " ⚪ ⣏ $name ⣹		→ $fileName ($modifiedTime)\n";

		}
	}

}
