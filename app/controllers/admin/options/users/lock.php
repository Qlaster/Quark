<?php

	if (! $APP->user->logged()) 
	{
		unset($_SESSION['lock']);
		header('Location: '.$APP->url->home().'admin/login');
		return false;
	}


	if (!isset($_SESSION['lock']))
	{
		$_SESSION['lock'] = $_SERVER["HTTP_REFERER"];
		if (!$_SESSION['lock']) $_SESSION['lock'] = 'admin/';
	}

	$content['profile'] = $APP->user->logged();
	//Прикрепляем базовую страницу
	$content['base'] = $APP->url->home();

	//Статическая GUI-структура — компаньон-ini [view] + перевод
	$cfg = $APP->config->get();
	$content = array_replace_recursive($content, (array) $cfg['view']);
	$content = $APP->l10n->translate($content);
	$content['title'] = $content['title']['head'];

	$APP->template->file('admin/lockscreen.html')->display($content);
