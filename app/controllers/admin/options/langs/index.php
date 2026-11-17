<?php

	$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);

	//Статическая GUI-структура — компаньон-ini [view] + перевод
	$cfg = $APP->config->get();
	$content = array_replace_recursive($content, (array) $cfg['view']);
	$content = $APP->l10n->translate($content);

	//Шаблон ждёт <title> скаляром — узел сплющиваем до head
	$content['title'] = $content['title']['head'];

	$l10nPath = $_ENV['facades']['app'].'/l10n.ini';
	$l10nCfg  = (array) $APP->config->get($l10nPath);
	$locales  = (array) ($l10nCfg['locales'] ?? []);

	//Файлы локали на диске — для колонки «Файлы переводов»
	$have = (array) $APP->l10n->langs();

	//Сохранение — через config->set: l10n.ini переписывается
	//канонической сериализацией (комментарии в файле не сохраняются)
	if ($_SERVER['REQUEST_METHOD'] === 'POST')
	{
		//Форма настроек — секция [translate]
		if (is_array($_POST['settings'] ?? null))
		{
			$S = $_POST['settings'];

			$l10nCfg['translate']['enable'] = !empty($S['enable']) ? 1 : 0;
			$l10nCfg['translate']['lang']   = isset($locales[$S['lang'] ?? ''])
				? $S['lang']
				: ($l10nCfg['translate']['source'] ?? 'ru');
			$l10nCfg['translate']['cookie'] = preg_match('/^[a-zA-Z][a-zA-Z0-9_-]{0,31}$/', $S['cookie'] ?? '')
				? $S['cookie']
				: 'lang';

			$ok = $APP->config->set($l10nCfg, $l10nPath) !== false;
		}
		//Форма галочек — секция [locales]
		else
		{
			$enable = array_fill_keys(array_map('strtolower', array_map('strval', (array) ($_POST['enable'] ?? []))), true);

			foreach ($locales as $code => &$l) $l['enable'] = isset($enable[$code]) ? 1 : 0;
			unset($l);
			$l10nCfg['locales'] = $locales;

			$ok = $APP->config->set($l10nCfg, $l10nPath) !== false;
		}

		//Исход — один узел message.main (text + class из пресетов ini)
		$content['message']['main'] = $ok ? $content['message']['ok'] : $content['message']['err'];
	}

	//Строки таблицы — узлы {head, code, flag, files, checked} в list-контейнере
	foreach ($locales as $code => $l)
	{
		$content['form']['langs']['list'][$code] = [
			'head'    => $l['head'] ?? $code,
			'code'    => $code,
			'flag'    => $l['flag'] ?? '',
			'files'   => isset($have[$code]),
			'checked' => !empty($l['enable']) ? 'checked' : '',
		];
	}

	//Значения полей формы настроек — текущие из [translate]
	$tr = (array) ($l10nCfg['translate'] ?? []);
	$content['form']['settings']['fields']['enable']['checked'] = !empty($tr['enable']) ? 'checked' : '';
	$content['form']['settings']['fields']['cookie']['value']   = $tr['cookie'] ?? 'lang';
	foreach ($locales as $code => $l)
		$content['form']['settings']['fields']['lang']['list'][] = [
			'value'    => $code,
			'head'     => $l['head'] ?? $code,
			'flag'     => $l['flag'] ?? '',
			'selected' => ($tr['lang'] ?? $tr['source'] ?? '') === $code ? 'selected' : '',
		];

	$APP->template->file('admin/options/langs.html')->display($content);
