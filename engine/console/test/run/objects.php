<?php

	/*
	 * Секция objects — полный прогон объектного слоя по всем методам.
	 *
	 * 	./console test run objects
	 *
	 * Случайные имена коллекций/документов (проверка на коллизию),
	 * журнал созданного, чистка items + timeline — база возвращается
	 * в исходное состояние, проверяемое счётчиками строк.
	 */

	$pdo = $APP->objects->PDO_INTERFACE;

	//Снимок «до»: количество строк в обеих таблицах
	$before = [
		'items'    => (int) $pdo->query("SELECT COUNT(*) FROM items")->fetchColumn(),
		'timeline' => (int) $pdo->query("SELECT COUNT(*) FROM timeline")->fetchColumn(),
	];

	//Генератор свободного имени коллекции
	$freeColl = function () use ($APP)
	{
		do { $name = '_test_'.bin2hex(random_bytes(6)); }
		while (in_array($name, $APP->objects->collection_list()));
		return $name;
	};

	//Генератор свободного имени документа в коллекции
	$freeDoc = function ($coll) use ($APP)
	{
		do { $name = 'doc_'.bin2hex(random_bytes(6)); }
		while (in_array($name, $APP->objects->collection($coll)->names()));
		return $name;
	};

	$createdColls = [];   //коллекции для чистки items+timeline
	try
	{
		$coll  = $freeColl();
		$coll2 = $freeColl();
		$createdColls[] = $coll; $createdColls[] = $coll2;
		$o     = $APP->objects->collection($coll);
		$doc   = $freeDoc($coll);

		//── collection + create + collection_list ────────────────
		$o->create();
		if (!in_array($coll, $APP->objects->collection_list()))
			throw new Exception("create()/collection_list: коллекция $coll не появилась");
		echo "\t✓ create, collection_list\n";

		//── set/get базовый ──────────────────────────────────────
		$o->set($doc, ['head' => 'RU', 'body' => 'текст']);
		$v = $o->get($doc);
		if (($v['head'] ?? null) !== 'RU')
			throw new Exception('set/get: базовый документ не читается');
		echo "\t✓ set, get (база)\n";

		//── set/get по языкам: точная строка, промах → null ──────
		$o->lang('en')->set($doc, ['head' => 'EN']);
		$o->lang('uk')->set($doc, ['head' => 'UK']);

		if (($o->lang('en')->get($doc)['head'] ?? null) !== 'EN')
			throw new Exception('lang(en)->get: не тот документ');
		if ($o->lang('fr')->get($doc) !== null)
			throw new Exception('lang(fr)->get: промах должен дать null');
		if ($o->get($doc)['head'] !== 'RU')
			throw new Exception('get() после lang() — lang не сбросился');
		echo "\t✓ lang(x)->set/get, промах → null, сброс lang\n";

		//── каскад: первая найденная по приоритету ───────────────
		if (($o->lang(['fr','en',''])->get($doc)['head'] ?? null) !== 'EN')
			throw new Exception('каскад [fr,en,\'\']: должен добрать en');
		if (($o->lang(['fr',''])->get($doc)['head'] ?? null) !== 'RU')
			throw new Exception('каскад [fr,\'\']: должен добрать базу');
		if ($o->lang(['fr','de'])->get($doc) !== null)
			throw new Exception('каскад [fr,de]: ничего нет — null');
		echo "\t✓ lang([цепочка])->get\n";

		//── langs: семья документа ───────────────────────────────
		$langs = $o->langs($doc);
		if ($langs !== ['', 'en', 'uk'])
			throw new Exception('langs(): семья должна быть [\'\', en, uk], есть '.json_encode($langs));
		if ($o->langs($doc.'_nope') !== [])
			throw new Exception('langs(): несуществующий документ → []');
		echo "\t✓ langs\n";

		//── names: DISTINCT семьи ────────────────────────────────
		$doc2 = $freeDoc($coll);
		$o->set($doc2, 'простой');
		if (!in_array($doc, $o->names()) or !in_array($doc2, $o->names()))
			throw new Exception('names(): документы семей не в списке');
		if (count($o->names()) !== count(array_unique($o->names())))
			throw new Exception('names(): повторы — языки не схлопнулись в семьи');
		echo "\t✓ names\n";

		//── all: срез по языку ───────────────────────────────────
		$all = $o->all();
		if (($all[$doc]['head'] ?? null) !== 'RU' or ($all[$doc2] ?? null) !== 'простой')
			throw new Exception('all(): базовый срез неверен');
		$allEn = $o->lang('en')->all();
		if (($allEn[$doc]['head'] ?? null) !== 'EN' or isset($allEn[$doc2]))
			throw new Exception('all() по lang(en): должен быть только en-срез');
		$allChain = $o->lang(['en',''])->all();
		if (($allChain[$doc]['head'] ?? null) !== 'EN' or ($allChain[$doc2] ?? null) !== 'простой')
			throw new Exception('all() по цепочке: первый удачный язык семьи');
		echo "\t✓ all (база, срез, каскад)\n";

		//── set с массивом → warning + false ─────────────────────
		if (@$o->lang(['en','uk'])->set($doc.'_arr', 'x') !== false or $o->get($doc.'_arr') !== null)
			throw new Exception('set() с массивом lang: должна быть ошибка, ничего не записано');
		echo "\t✓ set + массив → отказ\n";

		//── copy/rename объекта: семья целиком ───────────────────
		$doc3 = $freeDoc($coll);
		$o->copy($doc, $doc3);
		if ($o->langs($doc3) !== ['', 'en', 'uk'])
			throw new Exception('copy(объект): семья переводов не скопировалась');
		$o->rename($doc3, $doc3.'_r');
		if ($o->langs($doc3.'_r') !== ['', 'en', 'uk'] or $o->get($doc3) !== null)
			throw new Exception('rename(объект): семья не переехала целиком');
		echo "\t✓ copy, rename объекта (семья)\n";

		//── del по языкам ────────────────────────────────────────
		$o->lang('en')->del($doc);
		if ($o->langs($doc) !== ['', 'uk'])
			throw new Exception('del(lang): снят должен быть только en');
		$o->lang(['uk'])->del($doc);
		if ($o->langs($doc) !== [''])
			throw new Exception('del(массив): снят только перечисленный язык');
		$o->del($doc);
		if ($o->langs($doc) !== [])
			throw new Exception('del(): семья должна снестись целиком');
		$o->del($doc3.'_r');
		echo "\t✓ del (язык, список, семья)\n";

		//── timeline: снимки пишутся ─────────────────────────────
		$tl = $APP->objects->timeline->select(['collection' => $coll]);
		if (!$tl)
			throw new Exception('timeline: снимков не записалось');
		echo "\t✓ timeline (снимков: ".count($tl).")\n";

		//── copy/rename/drop коллекции ───────────────────────────
		$o->collection($coll)->copy($coll2);
		if (!in_array($coll2, $APP->objects->collection_list()) or !$APP->objects->collection($coll2)->names())
			throw new Exception('copy(коллекция): содержимое не скопировалось');
		$APP->objects->collection($coll2)->rename($coll2.'_renamed');
		$createdColls[] = $coll2.'_renamed';
		if (!in_array($coll2.'_renamed', $APP->objects->collection_list()))
			throw new Exception('rename(коллекция): новое имя не появилось');
		$APP->objects->collection($coll2.'_renamed')->drop();
		if (in_array($coll2.'_renamed', $APP->objects->collection_list()))
			throw new Exception('drop(коллекция): не удалилась');
		echo "\t✓ copy, rename, drop коллекции\n";

		//── export/import: дамп строк таблицы ────────────────────
		$doc4 = $freeDoc($coll);
		$o->set($doc4, ['export' => 1]);
		$o->lang('en')->set($doc4, ['export' => 2]);

		$json = $o->export();
		$rows = json_decode($json, true);
		if (!is_array($rows) or !isset($rows[0]['collection'], $rows[0]['name']))
			throw new Exception('export(): дамп должен быть списком строк с collection/name/lang');

		//в дампе должна быть вся семья doc4 (база + en)
		$langsInDump = array_values(array_map(function ($r) { return $r['lang']; }, array_filter($rows, function ($r) use ($doc4) { return $r['name'] === $doc4; })));
		sort($langsInDump);
		if ($langsInDump !== ['', 'en'])
			throw new Exception('export(): семья языков не попала в дамп: '.json_encode($langsInDump));

		//фильтр по языку
		$rowsEn = json_decode($o->lang('en')->export(), true);
		if (count(array_filter($rowsEn, function ($r) { return $r['lang'] !== 'en'; })) !== 0)
			throw new Exception('export(): lang-фильтр отдал не-en строки');

		//фильтр по имени
		$rowsN = json_decode($o->export($doc4), true);
		if (count(array_filter($rowsN, function ($r) use ($doc4) { return $r['name'] !== $doc4; })) !== 0)
			throw new Exception('export(): name-фильтр отдал чужие имена');

		//round-trip: семья восстанавливается целиком
		$o->del($doc4);
		$APP->objects->import($json);
		if ($o->langs($doc4) !== ['', 'en'] or ($o->lang('en')->get($doc4)['export'] ?? null) !== 2)
			throw new Exception('import(): семья не восстановилась');

		//SKIP: существующая строка пропускается
		$APP->objects->import($json, \App\Facade\Objects::IMPORT_SKIP);
		//STRICT: коллизия → исключение
		try { $APP->objects->import($json, \App\Facade\Objects::IMPORT_STRICT); throw new Exception('STRICT не кинул исключение'); }
		catch (\Exception $e) { if (strpos($e->getMessage(), 'уже существуют') === false) throw $e; }

		$o->del($doc4);
		echo "\t✓ export, import (строки, фильтры, флаги)\n";

		echo "\t✓ Все методы objects зелёные\n";
	}
	finally
	{
		//Прибираемся полностью: коллекции + их следы в истории
		foreach ($createdColls as $c)
		{
			$APP->objects->collection($c)->drop();
			$APP->objects->timeline->delete(['collection' => $c]);
		}

		$after = [
			'items'    => (int) $pdo->query("SELECT COUNT(*) FROM items")->fetchColumn(),
			'timeline' => (int) $pdo->query("SELECT COUNT(*) FROM timeline")->fetchColumn(),
		];
		if ($after !== $before)
			throw new Exception('База не вернулась в исходное состояние: до '.json_encode($before).' после '.json_encode($after));
	}
