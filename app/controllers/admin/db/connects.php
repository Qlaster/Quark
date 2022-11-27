<?php

	error_reporting(E_ALL & ~E_NOTICE);

	$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);

	$content['table']['db'] = $APP->db->config['connect'];

	foreach ($content['table']['db'] as $db => &$value)
	{

		$value['link'] = "admin/db/table?base=$db";
		$value['size'] = '-';

		//проверим одключение
		if ($APP->db->connect($db))
		{
			$value['active'] = 'active';

			if ($value['type'] == 'sqlite')
			{
				$filename = $APP->db->config['settings']['sqlite']['path'].DIRECTORY_SEPARATOR.$value['dbname'];
				$value['size'] = round(filesize($filename)/1024/1024, 2);
			}
		}

	}

	$APP->template->file('admin/dbmanager/db_connects.html')->display($content);

