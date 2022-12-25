<?php


	//~ $content = $APP->controller->run('admin/autoinclude', $APP);
	//~ if (! $content) return false;

	if (! $APP->user->logged())
	{
		unset($_SESSION['lock']);
		header('Location: '.$APP->url->home().'admin/login');
		return false;
	}



	//Проверяем валидность введенного пароля
	$profile = $APP->user->logged();
	if (! $APP->user->login($profile['login'], $_POST['password']))
	{
		header('Location: '.$APP->url->home().'admin/options/users/lock');
		exit;
	}


	$page = $_SESSION['lock'];

	unset($_SESSION['lock']);

	header('Location: '.$page);


