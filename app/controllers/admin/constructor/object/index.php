<?php

	$content = $APP->controller->run('admin/autoinclude', $APP);
	
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
		$item['delete_link'] = 'admin/constructor/object/drop?collection='.urlencode(($_collection_name));
		$item['delete_head'] = $content['form']['collection']['button']['delete']['head'];
		$item['icon'] = 'fa fa-database';
		$content['catalog']['collection']['list'][] = $item;
	}
	
	
	
	
	
	$objects = (array) $APP->object->collection((urldecode($_GET['collection'])))->all();
	
	foreach ($objects as $_object_name => $_object) 
	{
		$d_collection = $_GET['collection'];   //Коллекция уже в base64 (к нам приходит в таком виде)
		$d_object = ($_object_name);
		
		$item['head'] = $_object_name;
		$item['link'] = "";
		$item['delete'] = $APP->url->home()."admin/constructor/object/del?collection=".urlencode($d_collection)."&object=".urlencode($d_object);
		$item['edit'] = $APP->url->home()."admin/constructor/object/edit?collection=".urlencode($d_collection)."&object=".urlencode($d_object);
		$content['catalog']['objects']['list'][] = $item;
	}
	
	//~ $content['catalog']['objects'] = 
	$content['catalog']['objects']['list'] = (array) $content['catalog']['objects']['list'];
	//~ print_r($content['catalog']['objects']); die;

	//~ $themelink = $APP->url->home()."views/admin/";
	$APP->template->file('admin/constructor/object/object_collection.html')->display($content);
