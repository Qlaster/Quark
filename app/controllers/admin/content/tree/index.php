<?php


	$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);


	//Прикрепляем страницы
	$pages = $APP->page->all($_GET['limit'], $_GET['offset']);
	//Отсортируем
	sort($pages);

	//~ print_r($pages); die;
	$result = [];

	//Построим дерево
	foreach ($pages as $_page)
	{
		//Разложим путь на массив и обратим порядок в обратную сторону
		$_array = array_reverse( explode(DIRECTORY_SEPARATOR, $_page['url']) );

		//Добавим прямую ссылку на страницу
		$_page['link'] = $APP->url->home().$_page['url'];
		$_page['edit'] = $APP->url->home()."admin/content/page/edit?url=".$_page['url'];

		$buffer = [];
		$prev_key = null;
		//Пройдемся по пути и выберем все элементы
		foreach ($_array as $_key => $_value)
		{
			//Если это последний элемент - вложим информацию, иначе просто создадим еще 1 уровень вложения
			$buffer[$_value] = $buffer ? $buffer : (object) $_page;
			//~ $buffer[$_value] = $buffer;
			//Удалим прошлую итерацию, т.к. если мы здесь - она еще не завершена
			if ($prev_key !== null) unset($buffer[$prev_key]);
			//Сохраним ключ на родительский элемент
			$prev_key = $_value;
		}
		//Объединима новую информацию с прежними сведениями
		$result = array_merge_recursive($result, $buffer);
	}

	$content['title'] = 'Структура страниц';

	$content['tree']['head'] = "Структура страниц сайта:";
	//~ $content['tree']['info'] = "Карта сайта позволяет нагляднее видеть структуру страниц";
	$content['tree']['host'] = $APP->url->host().$APP->url->home();
	$content['tree']['list'] = $result;

	//Генерируем ссылки
	//~ foreach ($content['catalog']['page'] as $key => &$value)
	//~ {
		//~ $value['link_view'] = $APP->url->home().$value['url'];
		//~ $value['link_edit'] = 'admin/content/page/edit?url='.$value['url'];
		//~ $value['link_del'] 	= 'admin/content/page/del?url='.$value['url'].'&lang='.$value['lang'];
	//~ }




	$APP->template->file('admin/content/page_tree.html')->display($content);

