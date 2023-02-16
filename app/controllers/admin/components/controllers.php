<?php


	$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);


	//Получим путь до директориии с контроллерами
	$controllersDir = $APP->controller->config['folder'];
	$pages = $APP->utils->files->listing($controllersDir, '*.php');
	$result = [];

	$content['title'] = 'Контроллеры приложения';

	//Построим дерево
	foreach ($pages as &$_page)
	{

		//Добавим прямую ссылку на страницу
		$_page = ['path'=>$_page, 'title'=>basename($_page, '*.php')];
		$_page['link']    = mb_substr($_page['path'], mb_strlen($controllersDir)+1);
		$_page['url']     = $_page['link'];
		$_page['version'] = date('d.m.Y H:i:s', filectime($_page['path']));
		$_page['html']    = round(filesize($_page['path'])/1024, 2) . ' Kb';
		$_page['sitemap'] = $_page['path'];
		$_page['public']  = '✓';
		$_page['edit']    = $APP->url->home()."admin/tools/codeeditor/?file=".$_page['path'];



		$buffer = [];
		$prev_key = null;

		//Разложим путь на массив и обратим порядок в обратную сторону
		$_array = array_reverse( explode(DIRECTORY_SEPARATOR, $_page['path']) );

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

	$content['tree']['head'] = "Список доступных контроллеров:";
	//~ $content['tree']['info'] = "Карта сайта позволяет нагляднее видеть структуру страниц";
	$content['tree']['host'] = $APP->url->host().$APP->url->home();
	$content['tree']['list'] = $result;

	$APP->template->file('admin/components/controllers.html')->display($content);

