<?php

	$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);

	//Если передали логин - будем править этого пользователя


	$user = $APP->user->logged();
	$login = $user['login'];

	header('Location: '.$APP->url->home()."admin/options/users/edit?login=$login");

