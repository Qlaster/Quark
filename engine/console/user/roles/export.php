<?php

	/*
	 * Экспорт ролей в ini-файл.
	 *
	 * 	./console user roles export [файл]
	 *
	 * Все роли разом: секция [role.<name>] = path = "action".
	 * Без файла — в stdout (pipe).
	 */

	$file = array_shift($args);

	$roles = (array) $APP->user->role->get();
	if (!$roles) throw new Exception("Ролей нет");

	$ini = '';
	foreach ($roles as $name => $rules)
	{
		$ini .= "[role.$name]\n";
		foreach ((array) $rules as $path => $action)
			$ini .= "$path = \"$action\"\n";
		$ini .= "\n";
	}

	if ($file) { file_put_contents($file, $ini); echo "Роли выгружены в '$file'\n"; }
	else        echo $ini;
