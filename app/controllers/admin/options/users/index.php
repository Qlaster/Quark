<?php


	//print_r($APP->account->all());

	$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);

	$cfg = $APP->config->get();

	$content['catalog']['users']['list'] = $APP->user->all();

	//Статическая GUI-структура — компаньон-ini [view] + перевод
	$content = array_replace_recursive($content, (array) $cfg['view']);
	$content['menu'] = (array) $cfg['menu'];
	$content = $APP->l10n->translate($content);

	$content['title'] = $content['title']['head'];

	//Что бы по алфавиту логинов=)
	//ksort($content['catalog']['users']['list']);



	//Крепим ссылки
	foreach ($content['catalog']['users']['list'] as &$user)
	{
		$user['link'] = 'admin/options/users/edit?login='.$user['login'];
	}


	$APP->template->file('admin/users/users.list.html')->display($content);
