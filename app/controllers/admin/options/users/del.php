<?php

	if ( ! $content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP])) return false;


	$APP->user->del($_GET['login']);

	//Если удалили последнего пользователя - создадим дефолтного
	if (count($APP->account->all()) == 0)
	{
		$APP->user->create_default();
		$user = $APP->user->all();
		$user = reset($user);

		//Добавим все права на него. А то как то не по мужски...
		$files = $APP->utils->files->listing('controllers/admin/', '*.php');

		foreach ($files as $value)
		{
			$fileinfo = pathinfo($value);
			$record = $fileinfo['dirname']."/".$fileinfo['filename'];
			$user['access'][$record] = true;
		}



		$APP->user->edit($user);
	}


	header('Location: '.$APP->url->home().'admin/options/users');
