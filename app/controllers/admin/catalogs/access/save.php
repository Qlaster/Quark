<?php
/*
 * access/save.php — сохранение прав доступа каталога.
 *
 * POST-формат (rules — массив строк, индекс порядковый):
 *	rules[i][section]             access|denied
 *	rules[i][subject]             логин или маска ('*', 'editor-*', 'ivan.petrov')
 *	rules[i][ops][]               имена операций: select insert update delete replace
 *	rules[i][fields][]            поля каталога (безусловный слот fields.*)
 *	rules[i][fields_scoped][op]   comma-строка масок для конкретной операции
 *	rules[i][where][*|op]         sql-фрагмент
 *
 * Правила перезаписывают узлы access.<каталог> / denied.<каталог> целиком;
 * правила других каталогов и масок каталогов не затрагиваются.
 * Комментарии в catalog.ini при записи не сохраняются (райтер ini).
 */

	try
	{
		$name = trim($_POST['catalog']);
		if (!$name) throw new Exception("Не указан каталог", 102);

		$OPS  = ['select','insert','update','delete','replace'];
		$LIST = function($v) { //массив или comma-строка → список уникальных непустых строк
			if (!is_array($v)) $v = explode(',', (string) $v);
			return array_values(array_unique(array_filter(array_map('trim', $v))));
		};

		$config = $APP->catalog->config();
		unset($config['access'][$name], $config['denied'][$name]);

		foreach ((array) $_POST['rules'] as $row)
		{
			$subject = trim((string) $row['subject']);
			if ($subject === '') continue;

			$sec  = ($row['section'] === 'denied') ? 'denied' : 'access';
			$rule = [];

			//ops: чекбоксы; отмеченные все — сворачиваем в '*'
			if ($ops = array_values(array_intersect($LIST($row['ops'] ?? []), $OPS)))
				$rule['ops'] = json_encode(count($ops) === count($OPS) ? ['*'] : $ops);

			//fields: матрица — rules[i][fields][<op>][] список на операцию.
			//Все объявленные поля отмечены → не пишем (дефолт = все поля);
			//одинаковый список у всех 5 оп → сворачиваем в общий слот fields='["..."]'
			//Поля типа 'id' в матрице нет — в полный набор их не считаем
			$declared = [];
			foreach ((array) ($config['list'][$name]['field'] ?? []) as $f => $def)
				if (($def['type'] ?? '') !== 'id') $declared[] = $f;
			sort($declared);
			$fields = [];
			foreach ($OPS as $op)
			{
				if (!in_array($op, $ops)) continue;         //операция не включена — строки нет
				$list = $LIST($row['fields'][$op] ?? []);
				if (!$list) continue;                        //пусто — не пишем (UI не допускает)
				sort($list);
				if ($list !== $declared) $fields[$op] = $list; //полный набор — опускаем
			}
			if ($fields)
			{
				$uniq = array_unique(array_map('json_encode', $fields));
				$rule['fields'] = (count($fields) === 5 && count($uniq) === 1)
					? reset($uniq)                              //все одинаковы — общий слот
					: array_map('json_encode', $fields);
			}

			//where: per-op строки; одинаковый sql у всех 5 → общий слот where='...'
			$where = [];
			foreach ($OPS as $op)
				if (isset($row['where'][$op]) and $sql = trim((string) $row['where'][$op]))
					$where[$op] = $sql;
			if ($where)
			{
				$uniq = array_unique(array_values($where));
				$rule['where'] = (count($where) === 5 && count($uniq) === 1)
					? reset($uniq)
					: $where;
			}

			if (!$rule) continue;

			$config[$sec][$name][$subject] = $rule;
		}

		//Сохраняем и сразу дым-тестим: access() читает свежий конфиг,
		//опечатки в ops/scope взорвутся исключением здесь, а не у пользователя
		$APP->catalog->config($config);
		$APP->catalog->access($name)->as('~smoke~');

		header("Location: admin/catalogs/access/edit?name=".urlencode($name));
		exit;
	}
	catch (Exception $e)
	{
		http_response_code(500);
		echo '<div style="padding:20px;color:red">Ошибка: ' . htmlspecialchars($e->getMessage()) . '</div>';
	}
