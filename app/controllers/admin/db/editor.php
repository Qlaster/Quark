<?php

	error_reporting(E_ALL & ~E_NOTICE);
	$content = $APP->controller->run('admin/autoinclude', $APP);

	//Имя базы, конструктор которой мы хотим открыть
	$dbname = $_GET['dbname'];
	
	if ($dbname)
	{
		//Нас попросили пересохранить параметры этого подключчения
		if ($_POST)
		{
			$_POST = array_merge($APP->db->config['connect'][$_POST['name']], $_POST);
			//Удаляем предыдущее подключение
			unset($APP->db->config['connect'][$_POST['name']]);
			unset($_POST['name']);
			//Создаем новые
			$APP->db->config['connect'][$_POST['head']] = $_POST;
			$APP->db->config_save();		
			
			header("Location: connects");
			exit;
		}		



		$config = $APP->db->config['connect'][$dbname];
		$content['dbinfo'] = $config;		
		$content['dbinfo']['name'] = $content['dbinfo']['head'];
	}


	

	$APP->template->file('admin/dbmanager/db_editor.html')->display($content);

