<?php

	/*
	 * Внутреннее: печатает кандидатов автодополнения (слова через пробел).
	 * Вызывается из bash-completion скрипта, см. `console completion`.
	 *
	 * 	./console complete <cword> <слово1> <слово2> ...
	 *
	 * Источники кандидатов:
	 * 	- файлы/каталоги engine/console (кроме '_*') — дерево команд
	 * 	- имена фасадов — первый токен
	 * 	- имена служб — хвост service-команд
	 */

	$cword = (int) array_shift($args);   //индекс дополняемого слова в COMP_WORDS
	$words = $args;                       //слова после 'console'

	//спускаемся по дереву команд по уже введённым сегментам
	$dir = $_ENV['console']['path'] ?? 'engine/console';
	$idx = 0;
	while ($idx < $cword - 1 and isset($words[$idx]))
	{
		$cand = $dir.'/'.$words[$idx];
		if (is_dir($cand)) { $dir = $cand; $idx++; continue; }
		break;
	}

	//кандидаты текущего уровня дерева
	$out = [];
	foreach (glob($dir.'/*') as $f)
	{
		$base = basename($f);
		if ($base === '' or $base[0] === '_') continue;
		$out[] = is_dir($f) ? $base : basename($f, '.php');
	}

	//первый токен — ещё и имена фасадов
	if ($idx === 0) $out = array_merge($out, array_keys($APP->__facades()));

	//хвост service-команд — имена служб
	if (($words[0] ?? '') === 'service'
		and in_array($words[1] ?? '', ['run', 'start', 'stop', 'status', 'output'])
		and $cword >= 3)
		$out = array_keys($APP->service->list());

	echo implode(' ', array_unique($out));
