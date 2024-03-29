<?php

	$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);

	//Подгружаем конфигурацию
	$config = $APP->config->get();

	//Подгружаем локаль конфига
	$content = array_merge($content, $config['ru']);


	//Если передали данные - сохраним их
	if (($_POST) && ($_GET['name']))
	{
		//Сохраним обновленный объект
		$APP->object->collection('form')->set($_POST['name'], $_POST);
		//Если ввели новое имя для объекта - то удалим старый
		if ($_GET['name'] != $_POST['name'])
		{
			$APP->object->collection('form')->del($_GET['name']);
			header('Location: ?name='.urlencode( $_POST['name'] ));
		}
	}


	//Получаем все формы
	$form_list = $APP->object->collection('form')->all();



	//Клеим кнопочки и информацию
	foreach ($form_list as $form_key => $form_data)
	{
		unset($item);
		if ($_GET['name'] == $form_key) $item['active'] = true;
		//~ if (!$form_data['head']) $form_data['head'] = $form_key;

		//~ $item['head'] = $form_data['head'];
		$item['head'] = $form_key;
		$item['link'] = $APP->url->home().$APP->url->page().'?name='.urlencode( $form_key );
		$item['delete_link'] = 'admin/constructor/form/del?name='.urlencode( $form_key );
		$item['delete_head'] = $content['form']['collection']['button']['delete']['head'];
		$item['delete_icon'] = $content['form']['collection']['button']['delete']['icon'];
		$item['icon'] = 'fa  fa-list-alt';
		$content['catalog']['collection']['list'][] = $item;
	}



	$content['objectName'] = $_GET['name'];
	if ( $form = $APP->object->collection('form')->get( $_GET['name'] ) )
	{
		$content['catalog']['object'] = $form;
	}

	$content['catalog']['objects']['list'] = (array) $content['catalog']['objects']['list'];
	$content['catalog']['objects']['button']['edit']['link'] = 'admin/constructor/form/edit?name='.urlencode((string)$_GET['name']);
	//~ print_r($content['catalog']['objects']); die;

	//~ $themelink = $APP->url->home()."views/admin/";
	$APP->template->file('admin/constructor/forms/form_collection.html')->display($content);
