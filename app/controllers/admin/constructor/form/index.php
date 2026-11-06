<?php

	$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);

	//Подгружаем конфигурацию
	$config = $APP->config->get();

	//Подгружаем локаль конфига — компаньон [view] + перевод
	$content = array_replace_recursive($content, (array) $config['view']);
	$content = $APP->l10n->translate($content);


	//Язык документа: '' — базовая версия; save/del пишут выбранную строку
	$lang = $_GET['lang'] ?? '';

	//Если передали данные - сохраним их
	if (($_POST) && ($_GET['name']))
	{
		//Сохраним обновленный объект — в языковую строку
		$APP->object->collection('form')->lang($lang)->set($_POST['name'], $_POST);
		//Если ввели новое имя для объекта - то удалим старую языковую строку
		if ($_GET['name'] != $_POST['name'])
		{
			$APP->object->collection('form')->lang($lang)->del($_GET['name']);
			header('Location: ?name='.urlencode( $_POST['name'] ).'&lang='.urlencode($lang));
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
	//Каскад: перевод → база — как у галереи/объекта
	if ( $form = $APP->object->collection('form')->lang([$lang, ''])->get( $_GET['name'] ) )
	{
		$content['catalog']['object'] = $form;
	}

	//Переключатель языков документа: базовая версия — первый пункт списка (код '')
	$self  = 'admin/constructor/form/index?name='.urlencode((string) $_GET['name']);
	$langs = $APP->object->collection('form')->langs($_GET['name']);

	$content['language']['list'] = array_merge(
		['' => ['head' => $content['form']['langs']['default']]],
		$APP->l10n->locales(true));

	foreach ($content['language']['list'] as $code => &$l)
	{
		$l['link']    = $self.($code === '' ? '' : '&lang='.urlencode($code));
		$l['active']  = $code === $lang ? 'active' : false;
		$l['missing'] = !in_array($code, $langs, true);

		//Выделен → primary, перевода нет → default, есть в базе → success
		$l['class']   = $l['active'] ? 'primary' : ($l['missing'] ? 'default' : 'success');
	}
	unset($l);

	//Кнопка дропдауна показывает выбранный язык: код, флаг, подпись
	$content['form']['langs']['btn'] = [
		'code' => $lang,
		'flag' => $lang ? ($content['language']['list'][$lang]['flag'] ?? '') : '',
		'head' => $lang && isset($content['language']['list'][$lang])
			? $content['language']['list'][$lang]['head']
			: $content['form']['langs']['default'],
	];

	//Запрошен перевод, которого нет: показали базовую версию — предупреждаем
	if ($lang && !in_array($lang, $langs, true) && in_array('', $langs, true))
		$content['language']['notranslate'] = $content['form']['langs']['notranslate'];

	$content['catalog']['objects']['list'] = (array) $content['catalog']['objects']['list'];
	$content['catalog']['objects']['button']['edit']['link'] = 'admin/constructor/form/edit?name='.urlencode((string)$_GET['name']);
	//~ print_r($content['catalog']['objects']); die;

	//~ $themelink = $APP->url->home()."views/admin/";
	$APP->template->file('admin/constructor/forms/form.collection.html')->display($content);
