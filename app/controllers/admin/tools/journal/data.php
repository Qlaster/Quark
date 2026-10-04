<?php
/*
 * journal/data.php — server-side источник DataTables для журнала запросов.
 *
 * GET: datestart/dateend (dd.mm.yyyy, как на странице журнала)
 *      + стандартные параметры DataTables 1.10:
 *        draw, start, length, search[value], order[i][column|dir]
 *
 * Ответ: {draw, recordsTotal, recordsFiltered, data:[{...}]}
 *
 * Хранилище visits — файловое (*.log по дням), индексов нет:
 * полный скан диапазона при каждом запросе (дешёвый sequential I/O),
 * а в браузер уходит только запрошенная страница.
 */

	header('Content-Type: application/json');

	//Статика подписей деталей — компаньон-ini [view] + перевод
	$cfg = $APP->config->get();
	$D   = $APP->l10n->translate(array_replace_recursive([], (array) $cfg['view']))['detail'];

	//Авторизация обеспечена маршрутом route.ini: admin/* → autoinclude
	try
	{
		//Диапазон дат — те же параметры, что форма на странице
		$APP->visits->shear($_GET['datestart'] ?: date('d.m.Y'),
		                    $_GET['dateend']   ?: date('d.m.Y'));

		$search  = trim((string) ($_GET['search']['value'] ?? ''));
		$start   = max(0, (int) ($_GET['start']  ?? 0));
		$length  = (int) ($_GET['length'] ?? 25); if ($length < 1) $length = 25;
		$draw    = (int) ($_GET['draw'] ?? 0);

		//Колонка таблицы → индекс поля в строке лога (разделитель \t\t):
		//time, code, runtime, mempeak, ip, uri, type, osname, browsername,
		//browserversion, page, unique, userid, info, dump
		$FIELDS  = [0, 1, 2, 3, 5, 4, 6, 13];
		$NUMERIC = [0, 1, 2, 3]; //дата(unix), код, cpu, mem — числовое сравнение

		$orderCol = (int) ($_GET['order'][0]['column'] ?? 0);
		$orderDir = strtolower((string) ($_GET['order'][0]['dir'] ?? 'desc')) === 'asc' ? 1 : -1;
		$sortIdx  = $FIELDS[$orderCol] ?? 0;

		//Фильтр по коду ответа: '' = все, '4xx'/'5xx' = класс, '400' = >=400
		$errSel = (string) ($_GET['errors'] ?? '');
		$errOk  = $errSel === '' ? null : function ($line) use ($errSel) {
			$code = (int) explode("\t\t", $line, 3)[1];
			return $errSel === '400' ? $code >= 400
			                      : ($code >= (int) $errSel[0] * 100 && $code < ((int) $errSel[0] + 1) * 100);
		};

		//Скан сырого текста: поиск подстрокой по всей строке (все поля разом),
		//recordsTotal — весь диапазон, recordsFiltered — совпавшее
		$total = 0; $lines = [];
		while (($line = $APP->visits->next(true)) !== null)
		{
			$total++;
			if ($search !== '' and mb_stripos($line, $search) === false) continue;
			if ($errOk and !$errOk($line)) continue;
			$lines[] = $line;
		}

		//Файлы и строки хронологичны: сортировка по дате — просто reverse.
		//По остальным колонкам — usort с разбором нужного поля в компараторе
		if ($sortIdx === 0)
		{
			if ($orderDir === -1) $lines = array_reverse($lines);
		}
		elseif (in_array($sortIdx, $NUMERIC, true))
			usort($lines, function($a, $b) use ($sortIdx, $orderDir) {
				return $orderDir * (explode("\t\t", $a)[$sortIdx] + 0 <=>
				                    explode("\t\t", $b)[$sortIdx] + 0);
			});
		else
			usort($lines, function($a, $b) use ($sortIdx, $orderDir) {
				return $orderDir * strcmp(explode("\t\t", $a)[$sortIdx] ?? '',
				                          explode("\t\t", $b)[$sortIdx] ?? '');
			});

		//Страница: разбор только нужного среза.
		//Поля лога могут быть null — $h экранирует без deprecated на PHP 8.1+
		$h   = function ($v) { return htmlspecialchars((string) $v); };
		$out = [];
		foreach (array_slice($lines, $start, $length) as $line)
		{
			$r = $APP->visits->string_log_parse($line);

			//uri — пользовательские данные: экранируем (см. также title);
			//адрес до '?' — жирный фасет (поиск только пути),
			//хвост с параметрами — приглушённый фасет (ищет всю строку)
			$uri  = urldecode($r['uri']);
			$show = $h(mb_strimwidth($uri, 0, 100, '...'));
			$q    = mb_strpos($show, '?');
			$path = $q === false ? $uri : mb_substr($uri, 0, mb_strpos($uri, '?'));

			$cell = '<span title="'.$h($uri).'">'
				.'<span class="j-facet" data-search="'.$h($path).'">'
				.($q === false ? '<b>'.$show.'</b>'
				              : '<b>'.mb_substr($show, 0, $q).'</b>')
				.'</span>'
				.($q === false ? '' : '<span class="j-facet text-muted" data-search="'
				    .$h($uri).'">'.mb_substr($show, $q).'</span>')
				.'</span>';
			$ip   = $h($r['ip']);
			$code = (int) $r['code'];

			//детали записи — раскрывашка по клику на строке;
			//поля, которых нет в колонках: страница, unique, uid, версия, дамп целиком
			$detail = '<dl class="dl-horizontal journal-detail">'
				.'<dt>'.$D['uri'].'</dt><dd><code>'.$h($uri).'</code></dd>'
				.'<dt>'.$D['client'].'</dt><dd>'.$h(
				    trim($r['type'].' '.$r['browsername'].' '.$r['browserversion'].' '.$r['osname'])).'</dd>'
				.'<dt>'.$D['page'].'</dt><dd><code>'.$h($r['page']).'</code></dd>'
				.'<dt>'.$D['unique'].'</dt><dd>'.($r['unique'] ? $D['yes'] : $D['no']).'</dd>'
				.'<dt>'.$D['uid'].'</dt><dd><code>'.$h($r['userid']).'</code></dd>'
				.'<dt>'.$D['info'].'</dt><dd>'.($r['info']
				    ? '<code>'.$h($r['info']).'</code>'
				    : '<span class="text-muted">'.$D['no'].'</span>').'</dd>'
				.'<dt>'.$D['dump'].'</dt><dd>'.($r['dump']
				    ? '<pre>'.$h(
				        json_encode(json_decode($r['dump']), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
				        ?: $r['dump']).'</pre>'
				    : '<span class="text-muted">'.$D['no'].'</span>').'</dd>'
				.'</dl>';

			$out[] = [
				'date'    => date("d.m.Y H:i:s", (int) $r['time']),
				'code'    => $code . ($code > 400
				             ? ' <i class="fa fa-warning text-warning"></i>' : ''),
				//медленные запросы (>= 1 сек) — жирным, сразу бросаются в глаза
				'runtime' => $r['runtime'] >= 1 ? '<b>'.$r['runtime'].'</b>' : $r['runtime'],
				'mempeak' => $r['mempeak'].' kb',
				'uri'     => $cell,
				'ip'      => '<span class="j-facet" data-search="'.$ip.'">'.$ip.'</span>',
				'client'  => $h(trim($r['type'].' '.$r['browsername']
				             .' '.$r['osname'])),
				'info'    => $h($r['info']),
				'dump'    => $r['dump']
				             ? '<span title="'.$h($r['dump']).'">'
				               .$h(mb_strimwidth($r['dump'], 0, 50, '…')).'</span>'
				             : '',
				'detail'  => $detail,
			];
		}

		echo json_encode([
			'draw'            => $draw,
			'recordsTotal'    => $total,
			'recordsFiltered' => count($lines),
			'data'            => $out,
		]);
	}
	catch (Exception $e)
	{
		http_response_code(500);
		echo json_encode(['error' => $e->getMessage()]);
	}
