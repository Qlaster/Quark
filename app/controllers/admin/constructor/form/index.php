<?php

	$content = $APP->controller->run('admin/autoinclude', $APP);
	
	//Подгружаем конфигурацию
	$config = $APP->config->get();
	
	//Подгружаем локаль конфига
	$content = array_merge($content, $config['ru']);
	
	
	//Если передали данные - сохраним их
	if (($_POST) && ($_GET['name']))
		$APP->object->collection('form')->set($_GET['name'], $_POST);

	
	
	
	
	//Получаем все галереи
	$form_list = $APP->object->collection('form')->all();
	
	//~ print_r($form_list); die;
	
	//Клеим кнопочки и информацию
	foreach ($form_list as $form_key => $form_data) 
	{				
		unset($item);
		if ($_GET['name'] == $form_key) $item['active'] = true;		
		//~ if (!$form_data['head']) $form_data['head'] = $form_key;
		
		//~ $item['head'] = $form_data['head'];
		$item['head'] = $form_key;
		$item['link'] = $APP->url->home().$APP->url->page().'?name='.urlencode( $form_key );
		$item['delete_link'] = 'admin/constructor/gallery/del?name='.urlencode( $form_key );
		$item['delete_head'] = $content['form']['collection']['button']['delete']['head'];		
		$item['delete_icon'] = $content['form']['collection']['button']['delete']['icon'];		
		$item['icon'] = 'fa  fa-list-alt';
		$content['catalog']['collection']['list'][] = $item;
	}
	
	
	
	
	if ( $form = $APP->object->collection('form')->get( $_GET['name'] ) )
	{
		$content['catalog']['object'] = $form;
		$content['objectName'] = $_GET['name'];
		//~ $content['catalog']['objects']['button']['edit'] = 
	}

	$content['catalog']['objects']['list'] = (array) $content['catalog']['objects']['list'];
	$content['catalog']['objects']['button']['edit']['link'] = 'admin/constructor/forms/edit?name='.urlencode($_GET['name']);
	//~ print_r($content['catalog']['objects']); die;

	//~ $themelink = $APP->url->home()."views/admin/";
	$APP->template->file('admin/constructor/forms/form_collection.html')->display($content);
