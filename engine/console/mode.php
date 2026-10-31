<?php

	/*
	 * Режим сайта: ./console mode [<имя> [on|off]]
	 *
	 * 	./console mode                  — показать текущий режим
	 * 	./console mode maintenance      — включить режим (on — явно)
	 * 	./console mode maintenance on   — то же
	 * 	./console mode maintenance off  — выключить именно его
	 * 	./console mode off              — выключить текущий
	 *
	 * Механика: .env[mode].name выбирает режим, index.php до ядра
	 * инклюдит dependency-free контроллер из карты <имя>.controller.
	 * Запись точечная — правится строка name в секции, остальное
	 * в .env не трогаем.
	 */

	$name  = $args[0] ?? null;
	$state = $args[1] ?? null;
	$modes = (array) ($_ENV['mode'] ?? []);

	//без аргументов — текущее состояние
	if ($name === null)
	{
		echo "Режим: ".(($modes['name'] ?? '') ?: '(живой)')."\n";
		foreach ($modes as $k => $v)
			if (substr($k, -11) === '.controller') echo "\t".substr($k, 0, -11)." → $v\n";
		return;
	}

	//выключаем: 'off' без имени или '<текущий> off'
	$set = '';
	if ($name === 'off')
		$set = '';
	elseif ($state === 'off')
	{
		if (($modes['name'] ?? '') !== $name)
			throw new Exception("Сейчас не режим '$name' — выключать нечего");
		$set = '';
	}
	elseif ($state === 'on' or $state === null)
	{
		if (!isset($modes["$name.controller"]))       //режим не описан в карте
			throw new Exception("Неизвестный режим '$name' — нет mode.$name.controller в .env");
		if (!is_file($modes["$name.controller"]))      //режим описан, но контроллера нет
			throw new Exception("Контроллер режима '$name' отсутствует: {$modes["$name.controller"]}");
		$set = $name;
	}
	else
		throw new Exception("Использование: console mode [<имя> [on|off]]");

	//точечная правка name в [mode]
	$env = file_get_contents('.env');
	$rx  = '/(\[mode\][^\[]*?name[ \t]*=[ \t]*)[^\s]+/s';
	$env = preg_match($rx, $env)
		? preg_replace($rx, "\${1}\"$set\"", $env)
		: $env."\n[mode]\nname = \"$set\"\n";
	file_put_contents('.env', $env);

	echo $set ? "Режим: $set (сайт в обслуживании)\n" : "Режим снят — сайт живой\n";
