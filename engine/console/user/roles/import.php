<?php

	/*
	 * Импорт ролей из ini-файла / stdin.
	 *
	 * 	./console user roles import [файл]
	 *
	 * Файл не задан — читает stdin. Формат: [role.<name>] → path = "action"
	 * (выдаёт ./console user roles export). Старые файлы с секциями
	 * [preset.<name>] принимаются тоже.
	 */

	$file = array_shift($args);

	$text = $file ? @file_get_contents($file) : stream_get_contents(STDIN);

	if ($text === false || $text === '')
		throw new Exception("Нечего импортировать — передай файл или stdin");

	$ini = parse_ini_string($text, true);
	if (!$ini) throw new Exception("Не ini-грамматика");

	//[role.<name>] — актуальный формат; [preset.<name>] — легаси-файлы
	$packs = [];
	foreach ($ini as $section => $fields)
	{
		if (mb_strpos($section, 'role.') === 0)   $packs[mb_substr($section, 5)] = $fields;
		if (mb_strpos($section, 'preset.') === 0) $packs[mb_substr($section, 7)] = $fields;
	}

	if (!$packs) throw new Exception("Секций [role.*]/[preset.*] не нашлось");

	foreach ($packs as $pname => $rules)
	{
		$APP->user->role->set($pname, $rules);
		echo "Роль '$pname' импортирована (".count($rules)." правил)\n";
	}
