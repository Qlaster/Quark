<?php
/*
 * access/edit.php — форма прав доступа к записям каталога.
 * Редактирует секции [access]/[denied] конфига для конкретного
 * каталога (ключи <каталог>.<субъект-маска>).
 *
 * Правила из других масок каталога (access.* и т.п.) показываются
 * read-only в блоке "Наследовано" — правятся в своих каталогах.
 */

	$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);

	//Имя каталога — по нему адресуем узлы access.<имя>.* в конфиге.
	//Без параметра (например, редирект после сброса авторизации
	//обрезает query) — уходим на список каталогов
	if (!($name = $_GET['name'] ?? null))
	{
		header('Location: ../index');
		exit;
	}

	$config     = $APP->catalog->config();
	$catalogCfg = $config['list'][$name];
	if (!$catalogCfg) exit("Каталог '$name' не найден");

	$content['title']  = "Права доступа: $name";
	$content['name']   = $name;
	$content['ops']    = ['select','insert','update','replace','delete'];

	//Объявленные поля каталога — для клеток матрицы fields.
	//Поля типа 'id' исключаем: первичный ключ ни задать, ни поменять
	//нельзя — колонка для него бессмысленна
	$content['fields'] = [];
	foreach ((array) $catalogCfg['field'] as $f => $def)
		if (($def['type'] ?? '') !== 'id') $content['fields'][] = $f;

	//datalist для поля "Субъект": '*' (все/гость) + логины пользователей.
	//Список не ограничивает ввод — маски вроде editor-* вводятся свободно
	$content['logins'] = array_merge(['*'], array_keys((array) $APP->user->all()));

	//Собираем правила: собственные каталога и наследованные от масок-каталогов.
	//Наследованные — плоский read-only список: секция+маска показаны в строке
	$content['rules']     = ['access'=>[], 'denied'=>[]];
	$content['inherited'] = [];

	foreach (['access','denied'] as $sec)
		foreach ((array) ($config[$sec] ?? []) as $mask => $subjects)
			if (fnmatch($mask, (string) $name, FNM_CASEFOLD))
				foreach ((array) $subjects as $subject => $rule)
				{
					$row = [
						'subject' => $subject,
						'mask'    => $mask,
						'ops'     => _aclList($rule['ops'] ?? []),
						'fields'  => _aclFields($rule['fields'] ?? []),
						'where'   => _aclWhere($rule['where'] ?? []),
					];

					//правило объявлено прямо под именем каталога — редактируемое
					if ($mask === $name)
						$content['rules'][$sec][] = $row;
					else
						$content['inherited'][] = [
							'sec'     => $sec,
							'mask'    => $mask,
							'subject' => $subject,
							'ops'     => implode(', ', $row['ops']),
							'where'   => implode(' AND ', array_filter($row['where'])),
						];
				}

	$APP->template->file('admin/catalogs/access.html')->display($content);



	/*
	 * Значение списка (ops/fields): JSON-строка '["a","b"]',
	 * comma-строка 'a,b' или готовый массив → массив
	 */
	function _aclList($v)
	{
		if (is_array($v)) return $v;
		$j = json_decode((string) $v, true);
		if (is_array($j)) return array_values($j);
		return $v ? array_map('trim', explode(',', (string) $v)) : [];
	}

	//fields: скаляр — безусловный слот '*', массив — ключи-операции.
	//Нормализуем к виду { '*' => [маски], '<op>' => [маски] }
	function _aclFields($v)
	{
		if (!is_array($v)) return ['*' => _aclList($v)];
		$out = [];
		foreach ($v as $scope => $list)
			$out[$scope] = is_array($list) ? array_values($list) : _aclList($list);
		return $out;
	}

	//where: строка — безусловный слот '*', массив — ключи-операции.
	//Значения — sql-строки, не списки
	function _aclWhere($v)
	{
		return is_array($v) ? $v : ['*' => $v];
	}
