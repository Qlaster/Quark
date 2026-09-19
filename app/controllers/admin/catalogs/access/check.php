<?php
/*
 * access/check.php — отладочный эндпоинт: итоговые права логина к каталогу.
 * GET: catalog=<имя>, login=<логин|пусто=гость>
 * Ответ JSON: {select:{allowed,where,fields}, insert:{...}, ...}
 */

	header('Content-Type: application/json');

	try
	{
		if (!$name = $_GET['catalog']) throw new Exception("Не указан каталог", 102);

		$login = trim((string) $_GET['login']);
		$ACC   = $APP->catalog->access($name)->as($login !== '' ? $login : null);

		$out = [];
		foreach (['select','insert','update','replace','delete'] as $op)
			$out[$op] = [
				'allowed' => (bool) $ACC[$op],
				'where'   => $ACC['where'][$op],
				'fields'  => $ACC['fields'][$op],
			];

		echo json_encode(['login' => $login ?: 'гость', 'ops' => $out]);
	}
	catch (Exception $e)
	{
		http_response_code(500);
		echo json_encode(['error' => $e->getMessage()]);
	}
