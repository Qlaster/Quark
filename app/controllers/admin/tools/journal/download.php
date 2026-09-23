<?php
/*
 * journal/download.php — скачивание сырых лог-файлов за диапазон дат.
 *
 * GET: datestart/dateend (dd.mm.yyyy) — тот же отбор, что на странице;
 *      search/errors — фильтры таблицы: заданы → выгружаем один склеенный
 *      .log только с подходящими строками (пустая строка между днями).
 * Без фильтров (браузер не умеет принимать несколько файлов в одном ответе):
 *	1 файл    → отдаём как есть (.log)
 *	ZipArchive → zip-архив
 *	PharData  → tar-архив (ядро PHP, работает всегда)
 *	иначе     → файлы склеиваются в один лог
 */

	//Авторизация обеспечена маршрутом route.ini: admin/* → autoinclude

	$datestart = $_GET['datestart'] ?: date('d.m.Y');
	$dateend   = $_GET['dateend']   ?: date('d.m.Y');

	//shear возвращает список файлов диапазона (state нам не нужен — next() не вызываем)
	$files = (array) $APP->visits->shear($datestart, $dateend);
	if (!$files)
	{
		http_response_code(404);
		exit("За период $datestart — $dateend лог-файлов нет");
	}

	//unixtime в начале строки → человекочитаемый вид "[Y-m-d H:i:s]".
	//Применяется ко всем веткам выгрузки: и в файл, и внутрь архивов
	$humanizeLine = function ($line)
	{
		$p = explode("\t\t", $line, 2);
		if (is_numeric($p[0])) $p[0] = '['.date('Y-m-d H:i:s', (int) $p[0]).']';
		return $p[0]."\t\t".($p[1] ?? '');
	};
	$humanize = function ($log) use ($humanizeLine)
	{
		$out = '';
		foreach (file($log, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line)
			$out .= $humanizeLine($line)."\r\n";
		return $out;
	};

	//Экспорт с фильтрами таблицы: search (подстрока) + errors (класс кода) —
	//те же правила, что в data.php. Один .log, между непустыми днями пустая строка
	$search = trim((string) ($_GET['search'] ?? ''));
	$errSel = (string) ($_GET['errors'] ?? '');
	if ($search !== '' or $errSel !== '')
	{
		$errOk = $errSel === '' ? null : function ($line) use ($errSel) {
			$code = (int) explode("\t\t", $line, 3)[1];
			return $errSel === '400' ? $code >= 400
			                      : ($code >= (int) $errSel[0] * 100 && $code < ((int) $errSel[0] + 1) * 100);
		};

		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename="journal-'.$datestart.'_'.$dateend.'-filtered.log"');
		$first = true;
		foreach ($files as $f)
		{
			$out = '';
			foreach (file($f, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line)
			{
				if ($search !== '' and mb_stripos($line, $search) === false) continue;
				if ($errOk and !$errOk($line)) continue;
				$out .= $humanizeLine($line)."\r\n";
			}
			if ($out === '') continue;
			if (!$first) echo "\r\n";
			echo $out; $first = false;
		}
		exit;
	}

	//Один файл — отдаём сразу, без архива
	if (count($files) === 1)
	{
		$log = reset($files);
		$data = $humanize($log);
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename="'.basename($log).'"');
		header('Content-Length: '.strlen($data));
		echo $data;
		exit;
	}

	$arcname = 'journal-'.$datestart.'_'.$dateend;

	//zip — стандарт, открывается везде
	if (class_exists('ZipArchive'))
	{
		$path = tempnam(sys_get_temp_dir(), 'journal');
		$zip  = new ZipArchive;
		if ($zip->open($path, ZipArchive::OVERWRITE) === true)
		{
			foreach ($files as $f) $zip->addFromString(basename($f), $humanize($f));
			$zip->close();
			header('Content-Type: application/zip');
			header('Content-Disposition: attachment; filename="'.$arcname.'.zip"');
			header('Content-Length: '.filesize($path));
			readfile($path);
			unlink($path);
			exit;
		}
	}

	//tar через PharData — в ядре PHP, работает всегда
	if (class_exists('PharData'))
	{
		$path = sys_get_temp_dir().'/'.uniqid('journal').'.tar';
		$tar  = new PharData($path);
		foreach ($files as $f) $tar->addFromString(basename($f), $humanize($f));
		unset($tar); //флуш
		header('Content-Type: application/x-tar');
		header('Content-Disposition: attachment; filename="'.$arcname.'.tar"');
		header('Content-Length: '.filesize($path));
		readfile($path);
		unlink($path);
		exit;
	}

	//Ни одного архиватора — склеиваем в один лог с пустой строкой между днями
	header('Content-Type: application/octet-stream');
	header('Content-Disposition: attachment; filename="'.$arcname.'.log"');
	foreach ($files as $i => $f)
	{
		if ($i) echo "\r\n"; //пустая строка между файлами
		echo $humanize($f);
	}
	exit;
