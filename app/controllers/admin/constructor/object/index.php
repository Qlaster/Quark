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
		$item['link'] = $APP->url->home().$APP->url->page().'?collection='.urlencode(($_collection_name));

		$item['button']['delete']['link'] = 'admin/constructor/object/drop?collection='.urlencode(($_collection_name));
		$item['button']['delete']['head'] = $content['form']['collection']['button']['delete']['head'];

		$item['icon'] = 'fa fa-database';
		$content['catalog']['collection']['list'][] = $item;
	}





	$objects = (array) $APP->object->collection((urldecode((string)$_GET['collection'])))->all();

	foreach ($objects as $_object_name => $_object)
	{
		$d_collection = $_GET['collection'];   //Коллекция уже в base64 (к нам приходит в таком виде)
		$d_object = ($_object_name);

		$item['head'] = $_object_name;
		$item['link'] = "";

		$item['button']['editastext']['head'] = 'Редактор';
		$item['button']['editastext']['link'] = $APP->url->home()."admin/constructor/object/plaintext/edit?collection=".urlencode($d_collection)."&object=".urlencode($d_object);;
		$item['button']['editastext']['icon'] = 'fa-th-list';

		$item['button']['edit']['head'] = 'Конструктор';
		$item['button']['edit']['link'] = $APP->url->home()."admin/constructor/object/edit?collection=".urlencode($d_collection)."&object=".urlencode($d_object);
		$item['button']['edit']['icon'] = 'fa-puzzle-piece';

		$item['button']['delete']['head'] = 'Удалить';
		$item['button']['delete']['link'] = $APP->url->home()."admin/constructor/object/del?collection=".urlencode($d_collection)."&object=".urlencode($d_object);;
		$item['button']['delete']['icon'] = 'fa-trash';

		//Обработчик по умолчанию
		$item['link'] = $item['button']['editastext']['link'];

		$content['catalog']['objects']['list'][] = $item;
	}

	//~ $content['catalog']['objects'] =
	$content['catalog']['objects']['list'] = (array) $content['catalog']['objects']['list'];
	//~ print_r($content['catalog']['objects']); die;

	//~ $themelink = $APP->url->home()."views/admin/";
	$APP->template->file('admin/constructor/object/object_collection.html')->display($content);
