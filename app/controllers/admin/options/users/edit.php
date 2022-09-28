<?php

	$content = $APP->controller->run('admin/autoinclude', $APP);
	
	//Если передали логин - будем править этого пользователя
	if (isset($_GET['login']))	$user = $APP->user->get($_GET['login']);

	if (isset($user))
		$content['user'] = $user;

	
	
	//Список всех файлов, которым предстаит дать права
	//$files = $APP->utils->files->collection('controllers/admin', '*.php');
	
	
	$pathAdmin = $APP->controller->config['folder'].'/admin';
	
	$files = $APP->utils->files->listing($pathAdmin, '*.php');
	//~ $tree  = $APP->utils->files->tree($APP->controller->config['folder'].'/admin', '*.php');
	
	sort($files);
	
	//~ print_r($files);
	//Дополняем сведения и форматируем вывод
	foreach($files as &$_item)
	{	
		//Обрежем расположение директории админки
		//~ $_item = ltrim( mb_strcut($_item, strlen($APP->controller->config['folder'])), DIRECTORY_SEPARATOR );
		
		
		$info = pathinfo($_item);
		$info['fullname'] = $_item;
		
		//Если правило найдено - ставим галку. Если пользователь новый (создается), то по умолчанию тоже
		//~ if (isset($user['access'][$_item]) or (! isset($user)))
			//~ $info['access'] = "active";

		//Если правило найдено - ставим галку
		if (isset($user['denied'][$_item])) $info['denied'] ="active";
		
		$result[$info['dirname']][$info['basename']] = $info;		
	}
	
	//~ $content['user']['denied'] = $result;
	$content['denied'] = $result;

	$APP->template->file('admin/users/users_edit.html')->display($content);



	function files_convert_to_access($files)
	{
		foreach ($access as $key => $value) 
		{
			$info = pathinfo($value);
			$result[$info['dirname']][] = $info['filename'];
		}
		return $result;
	}



