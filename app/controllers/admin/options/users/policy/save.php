<?php

	$M = $APP->l10n->translate(array_replace_recursive([], (array) $APP->config->get()['view']))['messages'];

	//Точечный патч user.ini: комментарии и прочие секции не трогаем
	$file = 'engine/facades/user.ini';
	$text = file_get_contents($file);

	$render = function($v)
	{
		if ($v === 'true' or $v === 'false')           return $v;			//bool
		if (is_numeric($v))                            return $v;			//число
		if (preg_match('/^\s*\[.*\]\s*$/s', $v))       return $v;			//json-список
		if (preg_match('/^["\'].*["\']$/s', $v))       return $v;			//уже в кавычках
		return '"'.$v.'"';
	};

	//Валидация значения по ключу; null — ключ пропускаем
	$sanitize = function($sec, $key, $v)
	{
		//числовые — кламп >= 0 (POST подделывается, min="0" только в html)
		if (in_array($key, ['ip.ban','ip.bantime','lock.attempts','lock.minutes','password.length','lifetime'], true))
			return (string) max(0, (int) $v);

		if ($key === 'regenerate-id')
			return in_array($v, ['true','true'], true) || $v === true ? 'true' : 'false';

		if ($key === 'password.hash')
			return in_array($v, ['default','md5','bcrypt','argon2i','argon2id'], true) ? $v : null;

		//классы приходят массивом чекбоксов → json-список валидных
		if ($key === 'password.classes')
			return json_encode(array_values(array_intersect(
				(array) $v, ['lower','upper','digit','symbol'])));

		if (is_array($v)) return null;	//массивы принимают только известные ключи

		//строки — без переносов: одна строка ini, перенос сломает структуру файла
		return str_replace(["\r","\n"], '', (string) $v);
	};

	//Отклонённые валидацией ключи — отчитаемся в сообщении
	$rejected = [];

	//POST-поля: policy[a][b] или policy_a_b (точки в name PHP сворачивает в _) —
	//и та и та нотация → ini-ключ a.b в секции [policy]
	$patch = function($sec, $iniKey, $v) use(&$text, $render, $sanitize, &$rejected)
	{
		$v = $sanitize($sec, $iniKey, $v);
		if ($v === null) { $rejected[] = $sec.'.'.$iniKey; return; }

		//ключ внутри секции: [sec] ... key = <заменяемая строка>.
		//Граница секции — [ в начале строки (скобки в комментариях/json не считаем)
		$re = '/(\['.preg_quote($sec,'/').'\](?:(?!^\[)[\s\S])*?)(^\s*'.preg_quote($iniKey,'/').'\s*=\s*)[^\r\n]*/m';
		$text = preg_replace($re, '${1}${2}'.$render($v), $text, 1);
	};

	foreach ((array) $_POST as $name => $v)
	{
		//плоские имена policy_ip_allow → секция + ключ с подчёркиваниями→точки
		if (preg_match('/^(policy|session)_(.+)$/', $name, $m) && !is_array($v))
			$patch($m[1], str_replace('_', '.', $m[2]), $v);
		//вложенные массивы policy[a][b] / session[a]
		elseif (is_array($v) && in_array($name, ['policy','session'], true))
			foreach ($v as $key => $v2)
				foreach (is_array($v2) ? $v2 : [$v2] as $k2 => $v3)
					$patch($name, is_array($v2) ? $key.'.'.$k2 : $key, $v3);
	}

	if (file_put_contents($file, $text, LOCK_EX) === false)
	{
		http_response_code(500);	//js-ветка .fail() — ошибка уходит в warning
		echo $M['fail'];
	}
	else
		echo $M['ok'].($rejected ? ' '.$M['skipped'].' '.implode(', ', $rejected) : '');
