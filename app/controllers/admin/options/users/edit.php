<?php

	$content = $APP->controller->run('admin/autoinclude', $APP);
	
	//Если передали логин - будем править этого пользователя
	if (isset($_GET['login']))	$user = $APP->user->get($_GET['login']);

	if (isset($user))
		$content['user'] = $user;

	//~ else	//Если нет - то того, который залогинен
		//~ $content['user'] = $APP->account->logged();
	
	
	//Список всех файлов, которым предстаит дать права
	//$files = $APP->utils->files->collection('controllers/admin', '*.php');
	
	//~ echo $APP->controller->config['folder']; die;
	//~ echo $APP->controllers; die;
	//~ echo $APP->controllers->config['folder'].'/admin'; die;
	$files = $APP->utils->files->listing($APP->controller->config['folder'].'/admin', '*.php');
	//~ $tree  = $APP->utils->files->tree($APP->controller->config['folder'].'/admin', '*.php');
	
	sort($files);
	
	//Дополняем сведения и форматируем вывод
	foreach($files as $_item)
	{
		$info = pathinfo($_item);
		$info['fullname'] = $_item;
		
		//Если правило найдено - ставим галку. Если пользователь новый (создается), то по умолчанию тоже
		if (isset($user['access'][$_item]) or (! isset($user)))
			$info['access'] = "active";
				
		$result[$info['dirname']][$info['basename']] = $info;		
	}
	
	$content['user']['access'] = $result;
	
	
	//~ print_r($tree); exit;
	//~ print_r($result); exit;
	//~ print_r($files); exit;
	
//	$content['user']['access'] = (array) $content['user']['access'];


	
	foreach ($files as &$value) 
	{		
		$fileinfo = pathinfo($value);
		
		$record = $fileinfo['dirname']."/".$fileinfo['filename'];
		
		$buffer['head'] = $record;
		//~ $buffer['link'] = urlencode($record);
		
		//Если правило найдено - ставим галку. Если пользователь новый (создается), то по умолчанию тоже
		if (isset($user['access'][$record]) or (! isset($user)))
		{
			$buffer['active'] = true;	
		}
		else
		{
			unset($buffer['active']);	
		}
		//~ $content['user']['access'][$record] = $buffer; 
		//~ $value = $buffer; 
	}
	
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



