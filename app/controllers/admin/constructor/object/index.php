<?php

	$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);

	//Подгружаем конфигурацию
	$config = $APP->config->get();

	//Подгружаем локаль конфига — компаньон [view] + перевод
	$content = array_replace_recursive($content, (array) $config['view']);
	$content = $APP->l10n->translate($content);

	//Шаблон ждёт <title> скаляром — узел сплющиваем до head
	$content['title'] = $content['title']['head'];



	$collection_list = $APP->object->collection_list();

	foreach ($collection_list as $_collection_name)
	{
		    $item = [
				'head' => $_collection_name,
				'link' => $APP->url->home().$APP->url->page().'?collection='.rawurlencode(($_collection_name)),
				'icon' => 'fa fa-database',
				'active' => (bool) ($_GET['collection'] == $_collection_name)
			];

			// Действия для коллекции — статика из [view], динамика докручивается здесь
			$menu = $content['menu']['collection'];
			$menu['list']['rename']['data-link']       = $menu['list']['rename']['action'];
			$menu['list']['rename']['data-collection'] = $_collection_name;
			$menu['list']['dup']['link']    = $menu['list']['dup']['action'].'?collection=' . rawurlencode($_collection_name);
			$menu['list']['drop']['link']   = $menu['list']['drop']['action'].'?collection=' . rawurlencode($_collection_name);
			$menu['list']['export']['link'] = $menu['list']['export']['action'].'?collection=' . rawurlencode($_collection_name);
			$item['button']['actions'] = $menu;

		$content['catalog']['collection']['list'][] = $item;
		continue;


		//TODO: Предыдущая ветка кода для тестиования
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
	$d_collection = (string) $_GET['collection'];   //Коллекция

	foreach ($objects as $_object_name => $_object)
	{
		$d_object = (string) ($_object_name);

		$item = [
			'head' => $_object_name,
			'link' => ""
		];

		//Кнопки строки объекта — статика из [view], link докручиваем тут
		$item['button']['edit']        = $content['button']['edit'];
		$item['button']['edit']['link']= $APP->url->home() . $item['button']['edit']['action']
			. "?collection=" . rawurlencode($d_collection) . "&object=" . rawurlencode($d_object);


		$item['button']['editastext']         = $content['button']['editastext'];
		$item['button']['editastext']['link'] = $APP->url->home() . $item['button']['editastext']['action']
			. "?collection=" . rawurlencode($d_collection) . "&object=" . rawurlencode($d_object);

		$item['button']['timeline']         = $content['button']['timeline'];
		$item['button']['timeline']['link'] = $APP->url->home() . $item['button']['timeline']['action']
			. "?collection=" . rawurlencode($d_collection) . "&name=" . rawurlencode($d_object);


		//~ $item['button']['actions']['head'] = 'Действия';
		//~ $item['button']['actions']['list'][0]['head'] = 'Открыть в конструкторе';
		//~ $item['button']['actions']['list'][0]['link'] = $APP->url->home()."admin/constructor/object/edit?collection=".rawurlencode($d_collection)."&object=".rawurlencode($d_object);
		//~ $item['button']['actions']['list'][1]['head'] = 'Открыть в редакторе';
		//~ $item['button']['actions']['list'][1]['link'] = $APP->url->home()."admin/constructor/object/plaintext/edit?collection=".rawurlencode($d_collection)."&object=".rawurlencode($d_object);
		//~ $item['button']['actions']['list'][2]['head'] = 'Открыть в приложении';
		//~ $item['button']['actions']['list'][2]['link'] = '';

		//~ $item['button']['actions']['list'][3]['head'] = 'Переименовать';
		//~ $item['button']['actions']['list'][3]['data-link'] = 'admin/constructor/object/rename';
		//~ $item['button']['actions']['list'][3]['data-collection'] = $d_collection;
		//~ $item['button']['actions']['list'][3]['data-object'] = $_object_name;
		//~ $item['button']['actions']['list'][4]['head'] = 'Экспортировать';
		//~ $item['button']['actions']['list'][4]['link'] = $APP->url->home()."admin/constructor/object/export?collection=".rawurlencode($d_collection)."&object=".rawurlencode($d_object);
		//~ ////~ $item['button']['actions']['list'][5]['head'] = 'Импортировать';
		//~ ////~ $item['button']['actions']['list'][5]['link'] = '';
		//~ $item['button']['actions']['list'][6]['head'] = 'Удалить';
		//~ $item['button']['actions']['list'][6]['link'] = $APP->url->home()."admin/constructor/object/del?collection=".rawurlencode($d_collection)."&object=".rawurlencode($d_object);


		$menu = $content['menu']['object'];
		$menu['list']['rename']['data-link']       = $menu['list']['rename']['action'];
		$menu['list']['rename']['data-collection'] = $d_collection;
		$menu['list']['rename']['data-object']     = $_object_name;
		$menu['list']['export']['link'] = $APP->url->home() . $menu['list']['export']['action']
			. '?collection=' . rawurlencode($d_collection) . '&object=' . rawurlencode($d_object);
		$menu['list']['del']['link']    = $APP->url->home() . $menu['list']['del']['action']
			. '?collection=' . rawurlencode($d_collection) . '&object=' . rawurlencode($d_object);
		$item['button']['actions'] = $menu;

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
