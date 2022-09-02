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

	$APP->template->file('admin/lockscreen.html')->display($content);
