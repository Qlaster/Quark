<?php

	error_reporting(E_ALL & ~E_NOTICE);
	
	
	$content = $APP->controller->run('admin/autoinclude', $APP);


	//Прикрепляем страницы
	$content['catalog']['page'] = $APP->page->all($_GET['limit'], $_GET['offset']);
	
	
	//~ $_GET['id'] = '3';
	//~ $_GET['base'] = 'prod';
	//~ $_GET['table'] = 'page';
	
	
	
	//~ foreach ($content['table']['data']['menu'] as $key => &$value) 
	//~ {
		//~ $tablemenu[$key]['head'] = $value;
		//~ $tablemenu[$key]['link'] = "table?table=".$value;
		//~ if ($value == $_GET['table']) $tablemenu[$key]['active'] = 'active';
	//~ }	
	//~ $content['table']['data']['menu']	= $tablemenu;
	
	
	$base	= $_GET['base'];
	$table	= $_GET['table'];
	$id 	= $_GET['id'];
	
	$content['table']['action'] = "admin/db/table_record_set.php?base=$base&table=$table&id=$id";
	
	
	if (($_GET['base']) and ($_GET['table']) and ($_GET['id']))
	{
		//Формируем поля
		$columns = $APP->db->connect($_GET['base'])->table($_GET['table'])->Columns();
		
		//~ print_r($columns); die;
		
		foreach ($columns as $key => $value) 
		{
			$content['table']['data']['list'][$value['name']] = $value;
			
			$record = &$content['table']['data']['list'][$value['name']];
			
			$record['head'] = $APP->db->config['connect'][$_GET['base']]['table'][$_GET['table']][$record['name']]['head'];
			$record['type'] = $APP->db->config['connect'][$_GET['base']]['table'][$_GET['table']][$record['name']]['type'];
			
			$type = $APP->db->config['connect'][$_GET['base']]['table'][$_GET['table']][$record['name']]['type'];			
			$record['body'] = $APP->db->config['patterns'][$type]['body'];			
		}		

		//unset($record);
				
		//Получаем запись
		$buffer	 = $APP->db->connect($_GET['base'])->table($_GET['table'])->where('id = ?', $_GET['id'])->select()[0];
	
		foreach ($buffer as $key => &$value) 
		{
			$content['table']['data']['list'][$key]['name'] = $key;
			$content['table']['data']['list'][$key]['text'] = $value;
			$content['table']['data']['list'][$key]['head'] = $APP->db->config['connect'][$_GET['base']]['table'][$_GET['table']][$key]['head'];
		}
	}
	elseif (($_GET['base']) and ($_GET['table']))
	{
		//$content['table']['data']['list'] = $APP->db->connect($_GET['base'])->table($_GET['table'])->Columns();
		
		
		//Формируем поля
		$columns = $APP->db->connect($_GET['base'])->table($_GET['table'])->Columns();
		
		//~ print_r($columns); die;
		
		foreach ($columns as $key => $value) 
		{
			$content['table']['data']['list'][$value['name']] = $value;
			
			$record = &$content['table']['data']['list'][$value['name']];
			
			$record['head'] = $APP->db->config['connect'][$_GET['base']]['table'][$_GET['table']][$record['name']]['head'];
			$record['type'] = $APP->db->config['connect'][$_GET['base']]['table'][$_GET['table']][$record['name']]['type'];
			
			$type = $APP->db->config['connect'][$_GET['base']]['table'][$_GET['table']][$record['name']]['type'];			
			$record['body'] = $APP->db->config['patterns'][$type]['body'];			
		}	
		
		
		//~ foreach ($content['table']['data']['list'] as $key => &$value) 
		//~ {
			//~ $value['head'] = $APP->db->config['connect'][$_GET['base']]['table'][$_GET['table']][$value['name']]['head'];
		//~ }	
	}
	

	

	//~ print_r($content['table']['data']['list']); die;
	
	$APP->template->file('admin/dbmanager/db_record.html')->display($content);

	exit;
