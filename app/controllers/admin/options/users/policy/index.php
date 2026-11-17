<?php

	$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);

	//Статическая GUI-структура — компаньон-ini [view] + перевод
	$cfg = $APP->config->get();
	$content = array_replace_recursive($content, (array) $cfg['view']);
	$content = $APP->l10n->translate($content);
	$content['title'] = $content['title']['head'];

	//Текущая конфигурация из user.ini (сырые значения файла — как видит админ)
	$ini = (array) $APP->config->get('engine/facades/user.ini');
	$content['cfg']['policy']  = $ini['policy'];
	$content['cfg']['session'] = $ini['session'];

	//password.classes — json-строка → массив для чекбоксов
	$content['cfg']['policy']['password']['classes'] =
		(array) json_decode($content['cfg']['policy']['password']['classes'] ?: '[]', true);

	//Текущий ip админа и его статус (me — чтобы не перекрыть view-метку state.myip).
	//Адрес — из traffic->client_ip(): он умеет прокси-заголовки, а policy->ip()
	//нарочно смотрит только REMOTE_ADDR и для показа менее информативен
	$ip = $APP->traffic->client_ip();
	$content['state']['me'] = [
		'ip'     => $ip,
		'allow'  => $APP->user->policy->allowIp($ip),
		'banned' => $APP->user->policy->banned($ip),
	];

	//Живое состояние политики: бан-маркеры / локи / счётчики
	$now = time();
	$content['state']['rows'] = [];
	foreach ((array) $APP->user->policy->list() as $r)
	{
		if ($r['login'] === '')
		{
			$r['kind'] = 'ban';	//маркер бана адреса
			$r['perm'] = $r['until'] === '9999-12-31';
			$r['live'] = strtotime($r['until'].' UTC') > $now;
		}
		elseif ($r['until'])
		{
			$r['kind'] = 'lock';	//лок пары ip+login
			$r['live'] = strtotime($r['until'].' UTC') > $now;
		}
		else $r['kind'] = 'counter';	//просто счётчик промахов

		$content['state']['rows'][] = $r;
	}

	$APP->template->file('admin/users/users.policy.html')->display($content);
