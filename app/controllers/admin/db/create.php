<?php

	error_reporting(E_ALL & ~E_NOTICE);
	
	
	$content = $APP->controller->run('admin/autoinclude', $APP);

	
	$type     = $_POST['type'];
	$host     = $_POST['host'];
	$dbname   = $_POST['dbname'];
	$user     = $_POST['user'];
	$password = $_POST['password'];
	$head 	  = $_POST['head'];
	
	
	
	for ($i = 1; $i < 9999; $i++)
		if (! isset($APP->db->config['connect']["new_connect_$i"]) ) 
		{
			$head = "new_connect_$i";
			break;
		}
	
	

	$connect['type'] 	= $type;
	$connect['host'] 	= $host;
	$connect['dbname'] 	= $dbname;
	$connect['user'] 	= $user;
	$connect['password']= $password;
	$connect['head']	= $head;

	

	switch ($type) 
	{
		case 'mysql':
		case 'mssql':
		case 'pgsql':
			# пробуем подключаемся к базе данных  
			try 
			{  
				$DBH = new PDO("$type:host=$host;dbname=$dbname", $user, $password);  

				
				$APP->db->config['connect'][$head] = $connect;
				$APP->db->config_save();
			}  
			catch (PDOException $e) 
			{  
				echo "Ошибка установки соединения:".$e->getMessage();  
			}
			break;
		case 'sqlite':
			# пробуем подключаемся к базе данных  
			try 
			{  
				//~ echo "$type:engine/database/$dbname"; die;
				$DBH = new PDO("$type:engine/database/$dbname");  
				//~ print_r($connect); die;				
				$APP->db->config['connect'][$head] = $connect;
				$APP->db->config_save();
			}  
			catch (PDOException $e) 
			{  
				echo "Ошибка установки соединения:".$e->getMessage();  
			}
			break;
	}
	
	//~ $themelink = $APP->url->home()."views/admin/";
	//~ $APP->template->file('admin/db_construct.html')->themelink($themelink)->display($content);

