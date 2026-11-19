<?php

	/*
	 * Обновление платформы из дистрибутива.
	 *
	 * 	./console update [источник] [--dry] [--no-backup] [--no-test]
	 *
	 * 	источник    — папка дистрибутива или URL zip-архива;
	 * 	              по умолчанию — update.ini[update.distr.zip]
	 * 	--dry       — только план и отчёт, ничего не трогает
	 * 	--no-backup — не делать точечный бэкап перед apply
	 * 	--no-test   — пропустить ./console test в стейджинге
	 *
	 * Пайплайн:
	 * 	1. fetch    — дистрибутив в /tmp/quark-update-<ts>/pkg
	 * 	2. plan     — файлы пакета → ignore|merge|db|replace|new
	 * 	3. stage    — сборка «нового релиза под твои данные» в /tmp:
	 * 	              merge-файлы слиты с продом, базы — копии живых
	 * 	              с накатанной схемой пакета
	 * 	4. test     — ./console test run в стейдже (ворота; нет файла → skip)
	 * 	5. maintain — mode.name=maintenance в .env (сайт в 503, error/maintenance)
	 * 	6. backup   — точечный слепок затрагиваемых файлов → backups/update-<ts>/
	 * 	7. apply    — staging наливается поверх прода (copy→tmp→rename)
	 * 	8. release  — mode.name пуст, сайт живой
	 *
	 * Провал до фазы 5 — прод не тронут, staging в /tmp сотрётся ребутом.
	 * Провал в apply — restore из точечного бэкапа → flag off → error.
	 */

	$cfg = $APP->config->get(__DIR__.'/update.ini');
	$up  = (array) ($cfg['update'] ?? []);

	$dry      = !empty($flags['dry']);
	$noBackup = !empty($flags['no-backup']);
	$noTest   = !empty($flags['no-test']);

	$log = $APP->log->channel('update');
	$say = function ($m) { echo $m."\n"; };


	# ---------------------------------------------------------------- #
	# 0. Источник: папка | URL zip | дефолт из конфига                 #
	# ---------------------------------------------------------------- #

	$source  = $args[0] ?? ($up['distr']['zip'] ?? null);
	if (!$source) throw new Exception('Источник обновления не указан (аргумент или update.ini[update.distr.zip])');

	//Стейджинг в /tmp, а не в проекте: до фазы 5 прод не трогается
	//вообще, а брошенный на полпути каталог самоочистится ребутом
	$staging = sys_get_temp_dir().'/quark-update-'.date('Ymd_His');
	if (!mkdir($staging, 0775, true)) throw new Exception("Directory '$staging' creation denied");

	if (is_dir($source))
	{
		$distro = realpath($source);
		$say("Источник: $distro");
	}
	else
	{
		//Не папка — считаем URL'ом zip-архива
		//(copy() тянет http(s) при открытом allow_url_fopen)
		$say("Скачиваем: $source");
		$zipfile = "$staging/distro.zip";
		if (!@copy($source, $zipfile)) throw new Exception("Failed to download '$source'");

		$za = new ZipArchive();
		if ($za->open($zipfile) !== true) throw new Exception("Failed to open '$zipfile'");
		$za->extractTo("$staging/pkg");
		$za->close();

		//github-архив заворачивает всё в один корневой каталог (Quark-main/) — спускаемся
		$distro = "$staging/pkg";
		$inside = glob("$distro/*") ?: [];
		if (count($inside) === 1 and is_dir($inside[0])) $distro = $inside[0];
		$say("Распаковано в $distro");
	}

	//Маркеры платформы — и в пакете, и в цели. Проверяем ДО любых
	//копирований: не Quark-пакет или чужая директория — падаем здесь,
	//пока ничего не успели залить.
	foreach ((array) ($up['rootmarker'] ?? []) as $m)
	{
		if (!file_exists("$distro/$m"))  throw new Exception("Package '$distro' does not contain platform (no $m)");
		if (!file_exists(getcwd()."/$m")) throw new Exception("Target is not a platform root (no $m)");
	}


	# ---------------------------------------------------------------- #
	# 1. План: классификация файлов пакета                             #
	# ---------------------------------------------------------------- #

	$masks = function ($masks, $rel) {
		foreach ((array) $masks as $mask) if (fnmatch($mask, $rel)) return true;
		return false;
	};
	$isDb = function ($rel) {
		return in_array(strtolower(pathinfo($rel, PATHINFO_EXTENSION)), ['sqlite','db','dba']);
	};

	$plan = [];
	$rii  = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator($distro, FilesystemIterator::SKIP_DOTS),
		RecursiveIteratorIterator::SELF_FIRST);
	foreach ($rii as $f)
	{
		if (!$f->isFile()) continue;
		$rel = substr($f->getPathname(), strlen($distro)+1);

		//Порядок веток важен: db идёт первым — расширение решает
		//раньше масок (иначе *.db попал бы под merge/replace и потерял
		//схемо-синхронизацию). merge требует file_exists($rel): файл
		//маски merge.*, которого нет в проде, — это 'new', а не merge.
		//file_exists смотрит относительно CWD = корня проекта.
		if     ($isDb($rel))                          $class = 'db';
		elseif ($masks($up['ignore'] ?? [], $rel))    $class = 'ignore';
		elseif ($masks($up['merge']  ?? [], $rel) and file_exists($rel)) $class = 'merge';
		elseif (file_exists($rel))                    $class = 'replace';
		else                                          $class = 'new';

		if ($class !== 'ignore')
			$plan[$rel] = ['class'=>$class, 'src'=>$f->getPathname(), 'staged'=>"$staging/$rel"];
	}
	if (!$plan) throw new Exception('Пустой план — в пакете нечего применять');


	# ---------------------------------------------------------------- #
	# 2/3. Стейджинг: merge-файлы + базы + чистые файлы                #
	# ---------------------------------------------------------------- #

	//$stageDir — единственный mkdir-помощник. Вызывается ТРИЖДЫ:
	//в стейджинге (staged-путь), в apply (целевой $rel.new) и в
	//rollback (тот же $rel.new). Без него copy() падает на новых
	//файлах во вложенных каталогах, которых в проде ещё нет.
	$stageDir = function ($path) {   //проложить каталог для staged-файла
		$d = dirname($path);
		if (!is_dir($d) and !mkdir($d, 0775, true)) throw new Exception("Directory '$d' creation denied");
	};

	foreach ($plan as $rel => $p)
	{
		$stageDir($p['staged']);

		if ($p['class'] === 'merge')
		{
			//Тройной replace: база = твой прод, поверх — пакет, поверх
			//ещё раз прод. Итог: новые ключи пакета добавляются, но при
			//конфликте всегда побеждает твоё значение — пользовательские
			//настройки не затираются обновлением.
			//
			//Читаем/пишем напрямую, минуя $APP->config: get/set
			//навязывает .ini-суффикс (env-файлы без расширения недоступны),
			//а get() отдаёт РАЗВЁРНУТЫЙ конфиг (a.b.c → a=>b=>c), что
			//iniWrite обратно не развернёт — запись сломается.
			//
			//parse_ini_file() (builtin) не подходит: на наших ini он
			//спотыкается о ключи yes/no (BOOL_TRUE-варнинг, обрезанный
			//результат) и нестандартные значения. Core_Ini_Reader —
			//толерантный построчный парсер: секции + key=value сырыми,
			//точки в ключах НЕ раскрывает — именно та форма, которую
			//ожидают array_replace_recursive и iniWrite (round-trip
			//без потерь). Класс уже загружен вместе с $APP->config выше —
			//достаточно new, фасад его не реэкспортирует (reader() private).
			$reader = new \App\Facade\Core_Ini_Reader;
			$read = function ($f) use ($reader) { return is_file($f) ? $reader->parseIniFile($f) : []; };
			$merged = array_replace_recursive($read($rel), $read($p['src']), $read($rel));
			iniWrite($merged, $p['staged']);
		}
		elseif ($p['class'] === 'db')
		{
			//База из пакета. Если в проде она уже есть — копируем ЖИВУЮ
			//(с данными!) в стейдж и накатываем на неё схему пакета через
			//dbSync: новые таблицы/колонки добавляются, данные не трогаются.
			//Если базы в проде ещё нет (новая фича) — накатывать схему
			//некуда, берём пакетный файл как есть.
			//
			//Внимание: is_file, а не file_exists — теоретически $rel
			//может быть каталогом с таким именем.
			if (is_file($rel))
			{
				if (!copy($rel, $p['staged'])) throw new Exception("Failed to copy '$rel' to staging");
				dbSync($p['src'], $p['staged'], $rel, $log);
			}
			elseif (!copy($p['src'], $p['staged'])) throw new Exception("Failed to stage '$rel'");
		}
		else
			if (!copy($p['src'], $p['staged'])) throw new Exception("Failed to stage '$rel'");
	}


	# ---------------------------------------------------------------- #
	# Отчёт по плану                                                   #
	# ---------------------------------------------------------------- #

	$stat = ['merge'=>0,'db'=>0,'replace'=>0,'new'=>0];
	foreach ($plan as $p) $stat[$p['class']]++;
	$say("План: replace={$stat['replace']} merge={$stat['merge']} db={$stat['db']} new={$stat['new']}");

	if ($dry)
	{
		foreach ($plan as $rel => $p) $say("  [{$p['class']}] $rel");
		$say("--dry: стейджинг собран в $staging, прод не тронут");
		return;
	}

	$log->info("update started from $source (staging $staging)");


	# ---------------------------------------------------------------- #
	# 4. Тесты в стейдже — ворота                                      #
	# ---------------------------------------------------------------- #

	//Ворота: прогоняем тесты ИМЕННО в стейдже — тестируем новый код
	//с новыми конфигами, а не то, что сейчас лежит в проде.
	//Проверяем run.php (раннер секций test/run/*.php), а не test.php —
	//такого файла нет, старая проверка была мёртвой. Плюс сам console-
	//скрипт в корне стейджа. Нет — skip, не блокируем обновление.
	//exec с escapeshellarg на обоих аргументах — инъекций нет.
	if (!$noTest and is_file("$staging/engine/console/test/run.php") and is_file("$staging/console"))
	{
		$say('Тесты...');
		$php = PHP_SAPI === 'cli' ? PHP_BINARY : (PHP_BINDIR.'/php');
		exec('cd '.escapeshellarg($staging).' && '.escapeshellarg($php).' console test run 2>&1', $out, $code);
		if ($code !== 0)
		{
			$log->error("update aborted: staging tests failed (code $code): ".implode(' | ', $out));
			throw new Exception("Тесты состояния провалены (code $code):\n".implode("\n", $out));
		}
		$say('Тесты пройдены');
	}
	else $say('Тесты пропущены');


	# ---------------------------------------------------------------- #
	# 5. Обслуживание + точечный бэкап                                 #
	# ---------------------------------------------------------------- #

	//С этой строки начинается «опасная зона»: сайт уходит в 503
	//(error/maintenance), чтобы никто не читал полуприменённое.
	//Всё, что до этого упало, — прод не тронут.
	$this->dispatch(['mode', 'maintenance']);
	$log->info('mode: maintenance');

	//Точечный бэкап ТОЛЬКО затрагиваемых файлов (не всё дерево) —
	//restore при откате быстрый и не сносит чужие правки. Класс 'new'
	//пропускаем: файла в проде ещё нет, бэкапить нечего — поэтому
	//rollback их НЕ удаляет (известный недочёт, сироты остаются).
	$backup = 'backups/update-'.date('Ymd_His');
	if (!$noBackup)
	{
		foreach ($plan as $rel => $p)
		{
			//new-классу бэкапить нечего — файла в проде ещё нет
			if ($p['class'] === 'new' or !file_exists($rel)) continue;
			$dst = "$backup/$rel";
			$d = dirname($dst);
			if (!is_dir($d)) mkdir($d, 0775, true);
			copy($rel, $dst);
		}
		$say("Бэкап затронутых файлов: $backup");
		$log->info("backup: $backup");
	}


	# ---------------------------------------------------------------- #
	# 6. Apply: из глубины наружу (engine/core → корень последним)     #
	# ---------------------------------------------------------------- #

	//Сортируем по глубине пути: глубокие файлы — раньше, корневые
	//(index.php, .env) — последними. Смысл: промежуточные состояния
	//менее ломкие. Это эвристика, а не топологическая сортировка —
	//точного «ядро раньше поверх» не гарантирует.
	$sorted = array_keys($plan);
	usort($sorted, function ($a, $b) {
		return substr_count($b, '/') <=> substr_count($a, '/');   //глубже — раньше
	});

	$applied = [];
	try
	{
		foreach ($sorted as $rel)
		{
			//Механика пофайловой «атомарности»:
			//  1. staged → $rel.new   (copy; .new — временное имя)
			//  2. $rel → $rel.old      (rename в сторону — мгновенно,
			//     тот же ФС, без копирования данных)
			//  3. $rel.new → $rel      (rename на место — читатель видит
			//     либо старый, либо новый файл, «дыры» нет)
			//  4. $rel.old → unlink    (старое уже не нужно)
			//Между шагами 2 и 3 файла на месте нет — окно микроскопическое,
			//но maintenance-режим уже включён, читать некому.
			$tmp = "$rel.new";
			$stageDir($rel);   //для new-файлов каталога назначения может ещё не быть
			if (!copy($plan[$rel]['staged'], $tmp)) throw new Exception("Failed to copy staged '$rel'");
			if (file_exists($rel) and !rename($rel, "$rel.old")) throw new Exception("Failed to aside '$rel'");
			if (!rename($tmp, $rel)) throw new Exception("Failed to place '$rel'");
			if (file_exists("$rel.old")) unlink("$rel.old");
			$applied[] = $rel;
		}
	}
	catch (Throwable $e)
	{
		//Откат — best effort: восстанавливаем из бэкапа то, что было
		//успешно применено ($applied). @ перед copy/rename — ошибки
		//отката не должны маскировать первичную ($e). Флаг maintenance
		//снимаем ВСЕГДА — сайт не должен висеть в 503 из-за нашей ошибки.
		//Ограничение: у new-файлов бэкапа нет — после отката остаются
		//сиротами (см. комментарий у бэкапа выше).
		foreach ($applied as $rel)
			if (file_exists("$backup/$rel")) { $stageDir($rel); @copy("$backup/$rel", "$rel.new"); @rename("$rel.new", $rel); }
		$this->dispatch(['mode', 'off']);
		$log->error("update failed on '$rel', rolled back: ".$e->getMessage());
		throw $e;
	}


	# ---------------------------------------------------------------- #
	# 7. Release + отчёт                                               #
	# ---------------------------------------------------------------- #

	//Снимаем maintenance — сайт снова живой. Лог до $say: если echo
	//умрёт, в журнале останется факт применения.
	$this->dispatch(['mode', 'off']);
	$log->info("update applied: ".json_encode($stat, JSON_UNESCAPED_UNICODE));

	$say("Готово: replace={$stat['replace']} merge={$stat['merge']} db={$stat['db']} new={$stat['new']}");
	$say("Бэкап: $backup  |  Стейджинг: $staging (в /tmp — самоочистится)");


	# ---------------------------------------------------------------- #
	#   ini-писатель для merge-файлов: секции + ключи.                 #
	#   Комментарии и форматирование не переживают (принято).          #
	#   Принимает СЫРУЮ структуру parseIniFile: [секция][ключ]=значение,#
	#   ключи с точками пишутся как есть (a.b = v) — читатель развернёт #
	#   их обратно при следующем config->get.                           #
	# ---------------------------------------------------------------- #
	function iniWrite($cfg, $file)
	{
		//Сериализация значения: массивы → json-строка в кавычках
		//(наша ini-грамматика: [1,2] читается обратно массивом),
		//булевы → 1/0, числа — как есть, строки — в кавычках с
		//экранированием кавычки и бэкслеша. Чего НЕ экранируем:
		//перевод строки — значение с \n сломает строку ini
		//(для конфигов экзотика, но стоит знать).
		$val = function ($v) {
			if (is_array($v)) return '"'.json_encode($v, JSON_UNESCAPED_UNICODE).'"';
			if (is_bool($v))  return $v ? '1' : '0';
			return is_numeric($v) ? $v : '"'.addcslashes($v, '"\\').'"';
		};

		//Двухуровневая структура: [секция] → ключ=значение. Скаляр
		//вне секций пишется как top-level key=value. Более глубокой
		//вложенности быть не должно — сырые ключи с точками её не
		//создают (это и есть причина parseIniFile вместо config->get).
		$out = '';
		foreach ($cfg as $sec => $kv)
		{
			if (!is_array($kv)) { $out .= "$sec = ".$val($kv)."\n"; continue; }   //скаляр вне секций
			$out .= "[$sec]\n";
			foreach ($kv as $k => $v) $out .= "$k = ".$val($v)."\n";
			$out .= "\n";
		}
		if (file_put_contents($file, $out, LOCK_EX) === false) throw new Exception("Failed to write merged '$file'");
	}

	# ---------------------------------------------------------------- #
	#   Схемо-синхронизация sqlite-базы: референс пакета → живая       #
	#   Аддитивно: недостающие таблицы/колонки добавляются,             #
	#   конфликт типов — скип с warning. Данные не трогаем.            #
	# ---------------------------------------------------------------- #
	function dbSync($refFile, $liveCopy, $rel, $log)
	{
		//Класс 'db' ловит по РАСШИРЕНИЮ (.sqlite/.db/.dba) — а под него
		//попадают и не-базы: Thumbs.db из ckeditor'а, любая dll с
		//подходящим именем. Без проверки PDO('sqlite:...') открывает
		//файл «успешно», а первый же запрос к sqlite_master падает
		//с «file is not a database». Сигнатура — первые 16 байт
		//заголовка SQLite (ровно "SQLite format 3\0"). Проверяем ОБА
		//файла: и живую копию, и референс пакета.
		//Побочный эффект: не-sqlite .db из пакета НИКОГДА не обновит
		//прод (в стейдже лежит живая копия) — для Thumbs.db это даже
		//полезно, для редкого легитимного не-sqlite .db — молчаливый
		//пропуск обновления.
		$isSqlite = function ($f) {
			$h   = @fopen($f, 'rb');
			$sig = $h ? fread($h, 16) : '';
			if ($h) fclose($h);
			return $sig === "SQLite format 3\0";
		};
		if (!$isSqlite($liveCopy) or !$isSqlite($refFile))
		{
			$log->info("skip $rel: not a sqlite database — файл оставлен как есть");
			return;
		}

		$ref  = new PDO('sqlite:'.$refFile);
		$live = new PDO('sqlite:'.$liveCopy);
		$ref->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$live->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

		//Два разных квотирования — не путать:
		//  $qi — ИДЕНТИФИКАТОРЫ (имена таблиц/колонок): SQLite кавычит
		//    двойными кавычками, внутренние " удваиваются. Без этого
		//    PRAGMA table_info(account.service) парсится как
		//    schema.table — битый SQL. У нас реально есть таблица
		//    account.service в account.sqlite.
		//  PDO::quote — СТРОКОВЫЕ ЛИТЕРАЛЫ (значения в WHERE): одинарные
		//    кавычки с экранированием. Раньше тут был
		//    SQLite3::escapeString — он для литералов, а применялся
		//    к идентификаторам → та же бага с точками.
		$qi = function ($ident) { return '"'.str_replace('"', '""', $ident).'"'; };

		$tables = function ($db) {
			return $db->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
		};
		$cols = function ($db, $table) use ($qi) {
			$out = [];
			foreach ($db->query("PRAGMA table_info(".$qi($table).")") as $c)
				$out[$c['name']] = $c;
			return $out;
		};

		//Аддитивная миграция: итерируемся по таблицам РЕФЕРЕНСА (пакета).
		//Таблица есть в пакете, нет в живой → CREATE по DDL пакета
		//(sqlite_master.sql хранит исходный CREATE TABLE).
		//Таблица есть в обеих → сравниваем колонки: нет колонки →
		//ALTER TABLE ADD COLUMN; тип отличается → warning-скип
		//(менять тип = потерять данные, не наше дело).
		//Чего НЕ делаем: не удаляем таблицы/колонки, которых нет
		//в пакете (данные пользователя священны), не трогаем строки.
		foreach ($tables($ref) as $table)
		{
			if (!in_array($table, $tables($live)))
			{
				//таблица есть в пакете, нет в живой — создаём по DDL пакета
				$ddl = $ref->query("SELECT sql FROM sqlite_master WHERE name=".$ref->quote($table))->fetchColumn();
				$live->exec($ddl);
				$log->info("migrate $rel: +table $table");
				continue;
			}

			$lc = $cols($live, $table);
			foreach ($cols($ref, $table) as $name => $c)
			{
				if (!isset($lc[$name]))
				{
					$type = $c['type'] ?: 'TEXT';   //пустой тип → TEXT
					$live->exec("ALTER TABLE ".$qi($table)." ADD COLUMN ".$qi($name)." $type");
					$log->info("migrate $rel: $table +column $name $type");
				}
				//Сравнение типов регистронезависимое, но СТРОКОЕ:
				//INT vs INTEGER, VARCHAR(255) vs VARCHAR — лексически
				//разные, хотя affinity одинаковый → warning-шум без
				//реальной проблемы. Терпимо: это только лог.
				elseif (strtolower($lc[$name]['type']) !== strtolower($c['type']))
					$log->warning("migrate $rel: $table.$name type differs ({$lc[$name]['type']} vs {$c['type']}) — skipped");
			}
		}
	}
