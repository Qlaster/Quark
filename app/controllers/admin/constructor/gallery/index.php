<?php

	$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);

	//Подгружаем конфигурацию
	$config = $APP->config->get();

	//Подгружаем локаль конфига
	$content = array_merge($content, $config['ru']);

	//Получаем все галереи
	$form_list = $APP->object->collection('gallery')->all();

	//~ print_r($form_list); die;

	//Клеим кнопочки и информацию
	foreach ($form_list as $form_key => $form_data)
	{
		unset($item);
		if ($_GET['name'] == $form_key) $item['active'] = true;
		//~ if (!$form_data['head']) $form_data['head'] = $form_key;

		//~ $item['head'] = $form_data['head'];
		$item['head'] = $form_key;
		$form_key = urlencode( $form_key );
		$item['link'] = $APP->url->home().$APP->url->page().'?name='.$form_key;

		$item['button']['copy']             = $content['form']['collection']['button']['copy'];
		//~ $item['button']['copy']['link']     = 'admin/constructor/gallery/copy?name='.$form_key;
		//~ $item['button']['copy']['onclick']  = "copy('admin/constructor/gallery/copy?name=$form_key'); return false;";
		$item['button']['copy']['onclick']  = "copy('$form_key')";

		$item['button']['delete']         = $content['form']['collection']['button']['delete'];
		//~ $item['button']['delete']['link'] = 'admin/constructor/gallery/del?name='.$form_key;
		$item['button']['delete']['onclick'] = "remove('$form_key')";

		$item['icon'] = 'fa  fa-list-alt';
		$content['catalog']['collection']['list'][] = $item;
	}




	if ( $form = $APP->object->collection('gallery')->get( $_GET['name'] ) )
	{
		$content['catalog']['object'] = $form;
		//~ $content['catalog']['objects']['button']['edit'] =
	}

	$content['catalog']['objects']['list'] = (array) $content['catalog']['objects']['list'];
	$content['catalog']['objects']['button']['edit']['link'] = 'admin/constructor/gallery/edit?name='.urlencode($_GET['name']);
	//~ print_r($content['catalog']['objects']); die;

	//~ $themelink = $APP->url->home()."views/admin/";
	$APP->template->file('admin/constructor/gallery/gallery_collection.html')->display($content);
