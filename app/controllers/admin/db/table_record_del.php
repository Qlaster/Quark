<?php

	error_reporting(E_ALL & ~E_NOTICE);
	
	
	$content = $APP->controller->run('admin/autoinclude', $APP);


	//Прикрепляем страницы
	$content['catalog']['page'] = $APP->page->all($_GET['limit'], $_GET['offset']);
	
	

			
	if (($_GET['base']) and ($_GET['table']))
	{
		if ($_GET['id'])
		{
			//Изменяем запись - нам известен id
			$buffer	 = $APP->db->connect($_GET['base'])->table($_GET['table'])->where('id = ?', $_GET['id'])->delete();			
		}
	}
	
	

	//~ print_r($content['table']['data']['list']); die;
	

	//~ $themelink = $APP->url->home()."views/admin/";
	//~ $APP->template->file('admin/db_record.html')->themelink($themelink)->display($content);

	die;
