<?php

	error_reporting(E_ALL & ~E_NOTICE);
	$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);


	$base = $_GET['base'];
	$table = $_GET['table'];


	//Загружаем таблицы
	$content['table']['data']['head']	= 'Таблица';
	$content['table']['data']['menu']	= $APP->db->connect($_GET['base'])->Tables();
	$content['table']['data']['name']	= $base;

	//~ print_r($_GET['base']); die;

	//Если указанной таблицы не существует, обнуляем значение таблицы
	if ( array_search($table , $content['table']['data']['menu']) === false) $table = '';

	//Кнопки с названиями таблиц
	foreach ($content['table']['data']['menu'] as $key => &$value)
	{
		$tablemenu[$key]['head'] = $value;
		$tablemenu[$key]['link'] = "admin/db/table?base=$base&table=".$value;
		if ($value == $_GET['table']) $tablemenu[$key]['active'] = 'active';
	}
	$content['table']['data']['menu']	= $tablemenu;

	$content['table']['data']['btn']['create']['link'] = "admin/db/construct?base=$base";


	if (($base) and ($table))
	{
		//Формируем поля
		$columns = $APP->db->connect($_GET['base'])->table($_GET['table'])->Columns();

		//~ print_r($columns); die;

		foreach ($columns as $key => $value)
		{
			$content['table']['columns']['list'][$value['name']] = $value;

			$record = &$content['table']['columns']['list'][$value['name']];

			$record['head'] = $APP->db->config['connect'][$_GET['base']]['table'][$_GET['table']][$record['name']]['head'];
			$record['type'] = $APP->db->config['connect'][$_GET['base']]['table'][$_GET['table']][$record['name']]['type'];

			$type = $APP->db->config['connect'][$_GET['base']]['table'][$_GET['table']][$record['name']]['type'];
			$record['body'] = $APP->db->config['patterns'][$type]['body'];
		}

		unset($record);

		//Выгружаем данные
		$content['table']['data']['list']	= $APP->db->connect($_GET['base'])->table($_GET['table'])->select();



		foreach ($content['table']['data']['list'] as $key => &$row)
		{

			foreach ($row as $column_name => &$column)
			{
				switch ($content['table']['columns']['list'][$column_name]['type'])
				{
					case 'IMAGE':
						$column = "<a href='$column' target='_blank' > <img src='$column' height='60px'> </a>";
						break;
					case 'LINK':
						$column = "<a href='$column'>$column</a>";
						break;
					case 'AUDIO':
							if ($column) $column = "<audio src='$column' controls></audio>";
						break;
					default:

						break;
				}
			}

			//Прикрепляем
			$id = $row['id'];
			$content['table']['data']['btn'][$key]['edit']['link'] ="$('#ModalEditBody').load('admin/db/table_record_get?base=$base&table=$table&id=$id');";
			$content['table']['data']['btn'][$key]['del']['link'] = "$(this).load('admin/db/table_record_del?base=$base&table=$table&id=$id');";
		}
		$content['table']['data']['btn']['add']['link']   = "$('#ModalEditBody').load('admin/db/table_record_get?base=$base&table=$table');";

		//~ print_r($content['table']['data']['list']); die;

		$content['table']['data']['btn']['drop']['link'] = "admin/db/table_drop?base=$base&table=$table";

		//~ print_r($content['table']['data']['list']); die;




		//~ $table = $APP->db->connect($_GET['base'])->table($_GET['table'])->select();
		//~
		//~ $table = $APP->db->connect($_GET['base']);


		//~ print_r( $APP->db->connect($_GET['base'])->table($_GET['table']) ); die;

		//~ print_r($table); die;

		//Генерируем ссылки
		//~ foreach ($content['catalog']['page'] as $key => &$value)
		//~ {
			//~ $value['link_view'] = 'admin/content/page/add?page='.$value['url'];
			//~ $value['link_edit'] = 'admin/content/page/edit?url='.$value['url'];
			//~ $value['link_del'] 	= 'admin/content/page/del?url='.$value['url'].'&lang='.$value['lang'];
		//~ }
	}

	$APP->template->file('admin/dbmanager/db_table.html')->display($content);

