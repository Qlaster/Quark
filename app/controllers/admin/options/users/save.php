<?php


	if ( ! $APP->controller->run('admin/autoinclude', $APP)) return false;
	
	
	if ($_POST['login'] == '') return false;
	
	//Подгрузим оригинального (до изменения) пользователя, ибо он  имеет некоторые поля, которые мы можем не получить от формы
	$user = $APP->user->get($_POST['login']);
	//Заглянем в конфигурацию
	$config = $APP->config->get();
	
	//========================================================
	//				ОБРАБАТЫВАЕМ ПОЛЬЗОВАТЕЛЯ
	//========================================================
	

	$user['login'] 	= $_POST['login'];	unset($_POST['login']);
	$user['name'] 	= $_POST['name'];	unset($_POST['name']);
	$user['mail'] 	= $_POST['mail'];	unset($_POST['mail']);
	//$user['hash'] 	= $_POST['hash'];	
	$user['info'] 	= $_POST['info'];	unset($_POST['info']);
	$user['disable']= $_POST['disable'];	unset($_POST['disable']);
	$user['password']= $_POST['password'];	unset($_POST['password']);





	//========================================================
	//				РАЗБИРАЕМСЯ С ФАЙЛОМ АВАТАРКИ
	//========================================================
	if ($_FILES["avatar"]['name'] != '')
	{
	
		if ($_FILES["avatar"]["size"] > 1024*32*1024)
		{
			echo ("Размер файла превышает три  и два мегабайта");
			exit;
		}
		
		// Проверяем загружен ли файл	
		if (!is_uploaded_file($_FILES["avatar"]["tmp_name"]))
		{
			echo("Ошибка загрузки файла");
			exit;
		}
			
		// Если файл загружен успешно, перемещаем его
		// из временной директории в конечную		
		//$fin_file = "temp/upload/".$_FILES["avatar"]["name"];	
		
		$fin_file = $config['upload']['folder'].$user['login'].'.png';		
		//перемещяем в папку public
		move_uploaded_file($_FILES["avatar"]["tmp_name"], $fin_file);	
		//list($width, $height, $type, $attr) = getimagesize($fin_file);

		$user['logo'] 	= $fin_file;
	}
	
	


	//========================================================
	//				ПРАВА ПОЛЬЗОВАТЕЛЯ
	//========================================================
	$user['denied'] = $_POST['denied'];
	//~ unset($user['access']);
	//~ foreach ($_POST as $_name => $_value) 
	//~ {
		//~ if ($_value)
		//~ {
			//~ $user['access'][base64_decode($_name)] = $_value;
		//~ }
	//~ }
	

	
	try 
	{
		if ($APP->user->exists($user['login']))
			$APP->user->edit($user);
		else
			$APP->user->add($user);
	}
	catch (Exception $e) 
	{
    	echo $message = $e->getMessage();
	}


	header('Location: '.$APP->url->home().'admin/options/users');
	
	
	
	
	function ResizeImage($filename, $width = 200, $height = 200)
	{
		// получение новых размеров
		list($width_orig, $height_orig) = getimagesize($filename);
		 
		$ratio_orig = $width_orig/$height_orig;
		 
		if ($width/$height > $ratio_orig) {
		   $width = $height*$ratio_orig;
		} else {
		   $height = $width/$ratio_orig;
		}
		 
		// ресэмплирование
		$image_p = imagecreatetruecolor($width, $height);
		$image = imagecreatefromjpeg($filename);
		imagecopyresampled($image_p, $image, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);
		 
		// вывод
		imagejpeg($image_p, null, 100);	
	}
