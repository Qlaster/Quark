<?php


	if ( ! $content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP])) return false;


	if ($_POST['login'] == '') return false;

	//Подгрузим оригинального (до изменения) пользователя, ибо он  имеет некоторые поля, которые мы можем не получить от формы
	$user = $APP->user->get($_POST['login']);
	//Заглянем в конфигурацию
	$config = $APP->config->get();

	//========================================================
	//				ОБРАБАТЫВАЕМ ПОЛЬЗОВАТЕЛЯ
	//========================================================


	$user['login']    = $_POST['login'];    unset($_POST['login']);
	$user['name']     = $_POST['name'];     unset($_POST['name']);
	$user['email'] 	  = $_POST['email'];    unset($_POST['email']);
	$user['info']     = $_POST['info'];     unset($_POST['info']);
	$user['disable']  = $_POST['disable'];  unset($_POST['disable']);
	$user['password'] = $_POST['password']; unset($_POST['password']);
	$user['logo']     = $_POST['logo'];     unset($_POST['logo']);


	//Бланк имени файла аватарки
	$avatarFile = $config['upload']['folder'].$user['login'].'.png';

	//========================================================
	//				РАЗБИРАЕМСЯ С ФАЙЛОМ АВАТАРКИ
	//========================================================
	if ($_FILES["avatar"]['name'] != '')
	{

		if ($_FILES["avatar"]["size"] > 1024*32*1024) exit("Размер файла превышает 32 мегабайта");

		// Проверяем загружен ли файл
		if (!is_uploaded_file($_FILES["avatar"]["tmp_name"])) exit("Ошибка загрузки файла");

		// Если файл загружен успешно, перемещаем его
		// из временной директории в конечную
		//$fin_file = "temp/upload/".$_FILES["avatar"]["name"];

		if (!is_dir($config['upload']['folder'])) mkdir($config['upload']['folder'], 0775, true);

		//~ $fin_file = $config['upload']['folder'].$user['login'].'.png';
		//перемещяем в папку public
		move_uploaded_file($_FILES["avatar"]["tmp_name"], $avatarFile);
		//list($width, $height, $type, $attr) = getimagesize($fin_file);

		$user['logo'] = $avatarFile;
	}

	//Если нам требуется стереть файл аватарки
	if (!$user['logo'] and is_readable($avatarFile)) unlink($avatarFile);


	//========================================================
	//				ПРАВА ПОЛЬЗОВАТЕЛЯ
	//========================================================
	$user['denied'] = $_POST['denied'];


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
