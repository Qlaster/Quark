<?php

	ini_set( 'upload_max_size' , '20M' );
	ini_set( 'post_max_size', '20M');

	error_reporting(E_ALL & ~E_NOTICE);


	$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);


	//Прикрепляем страницы
	$content['catalog']['page'] = $APP->page->all($_GET['limit'], $_GET['offset']);


	//~ $_GET['id'] = '3';
	//~ $_GET['base'] = 'prod';
	//~ $_GET['table'] = 'page';

	//~ file_put_contents("/var/www/html/SANDBOX/vladimir/Qlaster/controllers/admin/db/1111mc1.txt", $_FILES);

	//~ print_r($_FILES); die;
	//~ print_r($_POST); die;



	if (($_GET['base']) and ($_GET['table']))
	{

		//Если нам подкинули на обработку файлы
		if (count($_FILES) >0)
		{
			foreach ($_FILES as $key => $file_option)
				if ($file_option['error'] == 0)
				{
					$dir = 'public/upload/'.date('Y/m/');
					if ( ! file_exists($dir) )
						if ( ! mkdir($dir, 0775, true) )
						{
							echo "не удалось получить доспуп на запись файлововй системы в $dir";
							exit;
						}

					if (move_uploaded_file($file_option['tmp_name'], $dir.$file_option['name']))
					{
						$_POST[$key] = $dir.$file_option['name'];
					}
					else
					{
						echo "При обработке файла произошли ошибки!";
						exit;
					}

				}
				elseif (($file_option['name'] != ''))
				{
					echo "Не удалось загрузить файл <b>".$file_option['name']."</b> Возможно, не поддерживает загрузку файлов такого размера. <br>";
				}
		}





		if ($_GET['id'])
		{
			//Изменяем запись - нам известен id
			$buffer	 = $APP->db->connect($_GET['base'])->table($_GET['table'])->where('id = ?', $_GET['id'])->update($_POST);
			if ($buffer) echo 'Запись успешно изменена. Если требуются актуальные данные - перезагрузите таблицу';
		}
		else
		{
			//Добавляем запись - нам не передали id
			unset($_POST['id']);
			$buffer	 = $APP->db->connect($_GET['base'])->table($_GET['table'])->insert($_POST);
			if ($buffer) echo 'Запись успешно добавлена. Если требуются актуальные данные - перезагрузите таблицу';
		}
	}



	//~ print_r($content['table']['data']['list']); die;


	//~ $themelink = $APP->url->home()."views/admin/";
	//~ $APP->template->file('admin/db_record.html')->themelink($themelink)->display($content);

	die;
