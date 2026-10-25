<?php

	/*
	 * Секция facades — smoke-инстанцирование всех фасадов.
	 *
	 * 	./console test facades
	 *
	 * Каждый фасад из __facades() поднимается через $APP->{name}:
	 * ленивый include + конструктор. Провал = исключение фасада.
	 * Исключения собираются — видим ВСЕ упавшие, а не первое.
	 */

	$failed = [];
	foreach ($APP->__facades() as $name => $file)
	{
		try
		{
			$APP->$name;
			echo "\t✓ $name\n";
		}
		catch (Throwable $e)
		{
			$failed[$name] = $e->getMessage();
			echo "\t✗ $name — ".$e->getMessage()."\n";
		}
	}

	if ($failed) throw new Exception('Упали фасады: '.implode(', ', array_keys($failed)));
