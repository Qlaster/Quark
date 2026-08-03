<?php

	$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);

	//Исходный объект — только внутри разрешённых директорий
	$old = $APP->files->jailPath($_GET['old_name'] ?? '');
	if (!$old or !file_exists($old)) exit('Исходный объект не найден');

	//Новое имя — только basename, никаких путей и переходов
	$name = basename((string) ($_GET['new_name'] ?? ''));
	if (!$name or $name == '.' or $name == '..') exit('Некорректное имя');

	//Цель — та же директория, что и источник (она уже в jail)
	$new = dirname($old).DIRECTORY_SEPARATOR.$name;
	if (file_exists($new)) exit('Объект с таким именем уже существует');

	//Пустой ответ — успех, текст — ошибка (как в file-delete и file-copy)
	exit(rename($old, $new) ? '' : 'Ошибка переименования');

