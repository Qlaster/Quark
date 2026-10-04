<?php

	$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);

	//Статика сообщений — компаньон-ini [view] + перевод
	$M = $APP->l10n->translate(array_replace_recursive([], (array) $APP->config->get()['view']))['messages'];

	//Исходный объект — только внутри разрешённых директорий
	$old = $APP->files->jailPath($_GET['old_name'] ?? '');
	if (!$old or !file_exists($old)) exit($M['nosrc']['text']);

	//Новое имя — только basename, никаких путей и переходов
	$name = basename((string) ($_GET['new_name'] ?? ''));
	if (!$name or $name == '.' or $name == '..') exit($M['badname']['text']);

	//Цель — та же директория, что и источник (она уже в jail)
	$new = dirname($old).DIRECTORY_SEPARATOR.$name;
	if (file_exists($new)) exit($M['exists']['text']);

	//Пустой ответ — успех, текст — ошибка (как в file-delete и file-copy)
	exit(rename($old, $new) ? '' : $M['fail']['text']);

