<?php

	$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);

	//Если передали логин - будем править этого пользователя
	if (isset($_GET['login']))	$user = $APP->user->get($_GET['login']);

	if (isset($user))
		$content['user'] = $user;



	//Список всех файлов, которым предстаит дать права
	//$files = $APP->utils->files->collection('controllers/admin', '*.php');

	//~ $pathAdmin = $APP->controller->config['folder'].'/admin';

	//~ $files = $APP->utils->files->listing($pathAdmin, '*.php');
	//~ $tree  = $APP->utils->files->tree($APP->controller->config['folder'].'/admin', '*.php');

	$files = $APP->controller->fetch('/admin');

	sort($files);

	//~ print_r($files);
	//Дополняем сведения и форматируем вывод
	foreach($files as &$_item)
	{
		$info = pathinfo($_item);
		$info['fullname'] = $_item;

		//Если правило найдено - ставим галку
		if (isset($user['denied'][$_item])) $info['denied'] ="active";

		$result[$info['dirname']][$info['basename']] = $info;
	}

	//~ $content['user']['denied'] = $result;
	$content['denied'] = $result;


	//Пресеты настроек
	foreach ((array) $APP->user->presets->get() as $name => $rules)
	{
		$content['presets'][$name] = ['head'=>$name, 'rules'=>$rules];
	}

	$APP->template->file('admin/users/users.edit.html')->display($content);




