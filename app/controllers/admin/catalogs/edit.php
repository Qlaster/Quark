<?php


	//Статика сообщений — компаньон-ini [view] + перевод
	$cfg = $APP->config->get();
	$M   = $APP->l10n->translate(array_replace_recursive([], (array) $cfg['view']))['messages'];

	$content['catalog'] = $APP->catalog->get($_GET['catalog']);

	//Права доступа к каталогу для текущего пользователя (гость → только *-маски)
	$user = $APP->user->logged();
	$ACC  = $APP->catalog->access($_GET['catalog'])->as($user ? $user['login'] : null);

	//Открытие формы — это будущая запись: есть id — update, нет — insert.
	//Запрещённая операция = форма не открывается вовсе
	$op = $_GET['id'] ? 'update' : 'insert';
	if (!$ACC[$op]) exit(sprintf($M['opforbidden']['text'], $op));

	//Поля формы ограничены разрешёнными для операции масками:
	//запрещённое поле просто не рисуется и не сможет быть отправлено
	$content['catalog']['field'] = array_filter((array) $APP->catalog->fields($_GET['catalog']),
		function($f, $name) use ($ACC, $op) { return $ACC->fieldAllowed($op, $name); },
		ARRAY_FILTER_USE_BOTH);

	//where-скоуп: запись вне разрешённого where не откроется на редактирование —
	//select вернет пустой результат
	if ($_GET['id'])
		$record = $APP->catalog->items($_GET['catalog'])
			->where($ACC['where']['update'])
			->where(['id'=>$_GET['id']])
			->select();

	$content['record'] = current((array) ($record??[]));


	//Отрисуем
	$APP->template->file('admin/catalogs/frame.edit.html')->display($content);

