<?php

	$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);

	//Подгружаем конфигурацию
	$config = $APP->config->get();

	//Подгружаем локаль конфига
	$content = array_merge($content, $config['ru']);



	$collection_list = $APP->object->collection_list();

	foreach ($collection_list as $_collection_name)
	{
		unset($item);
		if ($_GET['collection'] == $_collection_name) $item['active'] = true;
		$item['head'] = $_collection_name;
		$item['link'] = $APP->url->home().$APP->url->page().'?collection='.rawurlencode(($_collection_name));

		$item['button']['actions']['head'] = 'Действия';
		$item['button']['actions']['list'][0]['head'] = 'Переименовать';
		// $item['button']['actions']['list'][0]['link'] = '';
		$item['button']['actions']['list'][0]['data-link'] = 'admin/constructor/object/rename';
		$item['button']['actions']['list'][0]['data-collection'] = $_collection_name;
		$item['button']['actions']['list'][1]['head'] = 'Дублировать';
		$item['button']['actions']['list'][1]['link'] = 'admin/constructor/object/copy?collection='.rawurlencode(($_collection_name));
		$item['button']['actions']['list'][2]['head'] = 'Удалить';
		$item['button']['actions']['list'][2]['link'] = 'admin/constructor/object/drop?collection='.rawurlencode(($_collection_name));
		$item['button']['actions']['list'][3]['head'] = 'Экспортировать';
		$item['button']['actions']['list'][3]['link'] = 'admin/constructor/object/export?collection='.rawurlencode(($_collection_name));
		//~ $item['button']['actions']['list'][4]['head'] = 'Импортировать';
		//~ $item['button']['actions']['list'][4]['link'] = '';

		// $item['button']['delete']['link'] = 'admin/constructor/object/drop?collection='.rawurlencode(($_collection_name));
		// $item['button']['delete']['head'] = $content['form']['collection']['button']['delete']['head'];

		$item['icon'] = 'fa fa-database';
		$content['catalog']['collection']['list'][] = $item;

		unset($item);
	}





	$objects = (array) $APP->object->collection((urldecode((string)$_GET['collection'])))->all();

	foreach ($objects as $_object_name => $_object)
	{
		$d_collection = (string) $_GET['collection'];   //Коллекция уже в base64 (к нам приходит в таком виде)
		$d_object = (string) ($_object_name);

		$item['head'] = $_object_name;
		$item['link'] = "";


		$item['button']['edit']['head'] = 'Конструктор';
		$item['button']['edit']['link'] = $APP->url->home()."admin/constructor/object/edit?collection=".rawurlencode($d_collection)."&object=".rawurlencode($d_object);
		$item['button']['edit']['icon'] = 'fa-puzzle-piece';

		$item['button']['editastext']['head'] = 'Редактор';
		$item['button']['editastext']['link'] = $APP->url->home()."admin/constructor/object/plaintext/edit?collection=".rawurlencode($d_collection)."&object=".rawurlencode($d_object);
		$item['button']['editastext']['icon'] = 'fa-th-list';

		$item['button']['actions']['head'] = 'Действия';
		//~ $item['button']['actions']['list'][0]['head'] = 'Открыть в конструкторе';
		//~ $item['button']['actions']['list'][0]['link'] = $APP->url->home()."admin/constructor/object/edit?collection=".rawurlencode($d_collection)."&object=".rawurlencode($d_object);
		//~ $item['button']['actions']['list'][1]['head'] = 'Открыть в редакторе';
		//~ $item['button']['actions']['list'][1]['link'] = $APP->url->home()."admin/constructor/object/plaintext/edit?collection=".rawurlencode($d_collection)."&object=".rawurlencode($d_object);
		//~ $item['button']['actions']['list'][2]['head'] = 'Открыть в приложении';
		//~ $item['button']['actions']['list'][2]['link'] = '';
		$item['button']['actions']['list'][3]['head'] = 'Переименовать';
		$item['button']['actions']['list'][3]['data-link'] = 'admin/constructor/object/rename';
		$item['button']['actions']['list'][3]['data-collection'] = $d_collection;
		$item['button']['actions']['list'][3]['data-object'] = $_object_name;
		$item['button']['actions']['list'][4]['head'] = 'Экспортировать';
		$item['button']['actions']['list'][4]['link'] = $APP->url->home()."admin/constructor/object/export?collection=".rawurlencode($d_collection)."&object=".rawurlencode($d_object);
		//~ $item['button']['actions']['list'][5]['head'] = 'Импортировать';
		//~ $item['button']['actions']['list'][5]['link'] = '';
		$item['button']['actions']['list'][6]['head'] = 'Удалить';
		$item['button']['actions']['list'][6]['link'] = $APP->url->home()."admin/constructor/object/del?collection=".rawurlencode($d_collection)."&object=".rawurlencode($d_object);



		// Лучше иметь удаление в 2 клика, чем в 1. Вынес в меню
		// $item['button']['delete']['head'] = 'Удалить';
		// $item['button']['delete']['link'] = $APP->url->home()."admin/constructor/object/del?collection=".rawurlencode($d_collection)."&object=".rawurlencode($d_object);
		// $item['button']['delete']['icon'] = 'fa-trash';

		//Обработчик по умолчанию
		$item['link'] = $item['button']['editastext']['link'];

		$content['catalog']['objects']['list'][] = $item;
	}

	$content['catalog']['objects']['button']['add']['link'] = $APP->url->home()."admin/constructor/object/edit?collection=".rawurlencode($d_collection);

	//~ $content['catalog']['objects'] =
	$content['catalog']['objects']['list'] = (array) $content['catalog']['objects']['list'];
	//~ print_r($content['catalog']['objects']); die;

	//~ $themelink = $APP->url->home()."views/admin/";
	$APP->template->file('admin/constructor/object/object.collection.html')->display($content);
