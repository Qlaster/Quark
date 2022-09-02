<?php

	$content = $APP->controller->run('admin/autoinclude', $APP);
	
	//Подгружаем конфигурацию
	$config = $APP->config->get();
	
	//Подгружаем локаль конфига
	$content = array_merge($content, $config['ru']);
	
	
	
	$collection_list = $APP->object->collection_list();	
	$collection_list = array_keys($APP->object->collection('menu')->all());
	
	//~ print_r($collection_list); die;
	
	foreach ($collection_list as $_collection_name) 
	{
		unset($item);
		if (base64_decode($_GET['collection']) == $_collection_name) $item['active'] = true;
		$item['head'] = $_collection_name;
		$item['link'] = $APP->url->home().$APP->url->page().'?collection='.base64_encode($_collection_name);
		$item['delete_link'] = 'admin/constructor/menu/drop?collection='.base64_encode($_collection_name);
		$item['delete_head'] = $content['form']['collection']['button']['delete']['head'];
		$item['icon'] = 'fa fa-align-left';
		$content['catalog']['collection']['list'][] = $item;
	}
	
	
	
	
	
	//~ $objects = (array) $APP->object->collection(base64_decode($_GET['collection']))->all();
	$menu = (array) $APP->object->collection('menu')->get(base64_decode($_GET['collection']));
	
	//~ print_r($menu); die;
	
	foreach ((array) $menu['list'] as $_object_name => $_object) 
	{
		$d_collection = $_GET['collection'];   //Коллекция уже в base64 (к нам приходит в таком виде)
		$d_object = base64_encode($_object_name);
		
		$item['head'] = $_object_name;
		$item['link'] = "";
		$item['delete'] = $APP->url->home()."admin/constructor/menu/del?collection=$d_collection&object=$d_object";
		$item['edit'] = $APP->url->home()."admin/constructor/menu/edit?collection=$d_collection&object=$d_object";
		$content['catalog']['objects']['list'][] = $item;
	}
	
	//~ $content['catalog']['objects'] = 
	$content['catalog']['objects']['list'] = (array) $content['catalog']['objects']['list'];
	//~ print_r($content['catalog']['objects']); die;

	$APP->template->file('admin/constructor/menu_collection.html')->display($content);
