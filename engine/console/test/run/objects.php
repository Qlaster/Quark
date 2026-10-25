<?php

	/*
	 * Секция objects — round-trip объектного слоя.
	 *
	 * 	./console test run objects
	 *
	 * Запись → чтение → экспорт → удаление на scratch-документе
	 * test.__console (прибиваемся за собой).
	 */

	$coll = $APP->objects->collection('test');

	$coll->set('__console', ['head' => 'smoke', 'stamp' => (string) time()]);

	$doc = $coll->get('__console');
	if (!$doc or ($doc['head'] ?? null) !== 'smoke')
	{
		$coll->del('__console');
		throw new Exception('objects round-trip провален: документ не читается обратно');
	}
	echo "\t✓ запись и чтение\n";

	$json = $coll->export();
	if (!is_json($json) or !isset(json_decode($json, true)['__console']))
	{
		$coll->del('__console');
		throw new Exception('objects->export() не вернул документ');
	}
	echo "\t✓ экспорт\n";

	$coll->del('__console');
	echo "\t✓ прибрались\n";
