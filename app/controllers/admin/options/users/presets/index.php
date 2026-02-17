<?php

	$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);

	$user['denied'] = $APP->user->presets->get()[$_GET['name']];


	$files = $APP->controller->fetch('admin');
	sort($files);

	//Дополняем сведения и форматируем вывод
	foreach($files as &$_item)
	{

		$info = pathinfo($_item);
		$info['fullname'] = $_item;

		//Если правило найдено - ставим галку
		if (isset($user['denied'][$_item])) $info['denied'] ="active";

		$result[$info['dirname']][$info['basename']] = $info;
	}

	$content['denied'] = $result;


	//Пресеты настроек
	foreach ((array) $APP->user->presets->get() as $name => $rules)
	{
		//~ $content['presets'][$name] = ['head'=>$name, 'rules'=>$rules];
		$content['menu']['presets']['list'][$name] = ['head'=>$name, 'rules'=>$rules, 'link'=>'admin/options/users/presets/?name='.urlencode($name)];
	}

	if ($_GET['name'] and $content['menu']['presets']['list'][$_GET['name']])
		$content['menu']['presets']['list'][$_GET['name']]['active'] = 'active';


	$APP->template->file('admin/users/users.roles.html')->display($content);




