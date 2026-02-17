<?php


	//print_r($APP->account->all());

	$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);

	$content['catalog']['users']['list'] = $APP->user->all();

	$content['title'] = 'Пользователи';

	$content['menu'] = $APP->config->get()['menu'];

	//Что бы по алфавиту логинов=)
	//ksort($content['catalog']['users']['list']);



	//Крепим ссылки
	foreach ($content['catalog']['users']['list'] as &$user)
	{
		$user['link'] = 'admin/options/users/edit?login='.$user['login'];
	}


	$APP->template->file('admin/users/users.list.html')->display($content);
