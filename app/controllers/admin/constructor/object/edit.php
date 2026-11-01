<?php

	$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);

	//Статическая GUI-структура — компаньон-ini [view] + перевод
	$cfg = $APP->config->get();
	$content = array_replace_recursive($content, (array) $cfg['view']);
	$content = $APP->l10n->translate($content);

	$content['object'] = $_GET['object'];
	$lang              = $_GET['lang'] ?? '';

	//Шаблон ждёт <title> скаляром — узел сплющиваем до head
	$content['title'] = sprintf($content['title']['head'], $_GET['object']);

	//Переключатель языков документа: ссылку каждой локали клеит контроллер
	$self = 'admin/constructor/object/edit?collection='.urlencode($_GET['collection'])
	        .'&object='.urlencode($_GET['object']);

	//Языки документа — для поля class у локали
	$langs = $APP->object->collection($_GET['collection'])->langs($_GET['object']);

	//Переключатель языков: базовая версия — первый пункт списка (код '')
	$content['language']['list'] = array_merge(
		['' => ['head' => $content['form']['tree']['btn-lang']['head']]],
		$APP->l10n->locales(true));

	foreach ($content['language']['list'] as $code => &$l)
	{
		$l['link']    = $self.($code === '' ? '' : "&lang=".urlencode($code));
		$l['active']  = $code === $lang ? 'active' : false;
		$l['missing'] = !in_array($code, $langs, true);

		//Выделен → primary, перевода нет → warning, есть в базе → success
		$l['class'] = $l['active'] ? 'primary' : ($l['missing'] ? 'default' : 'success');
	}
	unset($l);

	//Кнопка дропдауна показывает выбранный язык: код и подпись
	if ($lang && isset($content['language']['list'][$lang]))
	{
		$content['form']['tree']['btn-lang']['code'] = $lang;
		$content['form']['tree']['btn-lang']['head'] = $content['language']['list'][$lang]['head'];
	}

	//Запрошен перевод, которого нет: get отдаст базовую версию — предупреждаем
	if ($lang && !in_array($lang, $langs, true) && in_array('', $langs, true))
		$content['language']['notranslate'] = $content['form']['tree']['notranslate'];

	$APP->template->file('admin/constructor/object/object.edit.html')->display($content);
