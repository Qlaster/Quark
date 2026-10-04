<?php


	$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);

	//Статическая GUI-структура — компаньон-ini [view] + перевод
	$cfg = $APP->config->get();
	$content = array_replace_recursive($content, (array) $cfg['view']);
	$content['menu']['folders'] = $cfg['folders'];
	$content = $APP->l10n->translate($content);
	$content['title'] = $content['title']['head'];

	//Текущая директория
	$path = getcwd();

	//Получаем путь (только внутри разрешённых директорий)
	if ($_GET['path'] and $jailed = $APP->files->jailPath($_GET['path'])) $path = $jailed;

	//Запрашиваем содержимое
	$glob = glob("$path/*");

	//Идентификатор потоковой загрузки — по нему сервер склеивает чанки
	$content['hash'] = md5(uniqid('', true));

	list($dir, $file) = [[],[]];
	foreach ($glob as $filename)
	{
		$info = stat($filename);

		$element['path'] 	= $filename;
		$element['head'] 	= $APP->files->basename($filename);
		$element['icon'] 	= icon_setter($filename, $cfg['patterns']);
		$element['load']	= 'admin/tools/filemanager/file-download?path='.$filename;
		$element['ctime'] 	= date('d.m.Y H:i:s', $info['ctime']);
		$element['isdir'] 	= is_dir ($filename);
		$element['isfile'] 	= is_file($filename);


		if ($element['isdir'])
		{
			$element['link'] 	= 'admin/tools/filemanager?path='.$path.'/'.$element['head'];
			$dir[] = $element;
		}
		if ($element['isfile'])
		{
			$element['link'] 	= 'admin/tools/codeeditor?file='.$path.'/'.$element['head'];
			$file[] = $element;
		}

		//~ echo $element['link'] ; var_dump( $element['isfile']  );
		//echo "$filename размер " . filesize($filename) . "\n";
	}
	//Объединям (что бы директории были первыми в списке, а потом файлы)
	$content['folder'] = array_merge((array) $dir, (array) $file);

	$content['path'] = $path;

	//Буфер обмена — имя объекта, если он запомнен в сессии
	if ($clipboard = $_SESSION['filemanager']['clipboard'] ?? null)
		$content['clipboard']['filename'] = basename($clipboard);

	//Кнопка назад
	$buffer = (array) explode('/', $path);
	array_pop($buffer);
	$content['menu']['buttons']['back']['link'] = 'admin/tools/filemanager?path='.implode('/', $buffer);


	//Возвращает иконки, соответствующие расширению и типу файла
	function icon_setter($filename, $patterns)
	{
		if (is_dir($filename)) return 'fa fa-folder';

		$ext = pathinfo($filename);
		$ext = $ext['extension'];

		if ( isset($patterns[$ext]) ) return $patterns[$ext];
		return 'fa fa-file';
	}

	//~ $themelink = $APP->url->home()."views/admin/";
	$APP->template->file('admin/tools/file-manager/file-manager.html')->display($content);
