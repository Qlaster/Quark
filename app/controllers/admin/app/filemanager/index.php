<?php


	$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);

	//Текущая директория
	$path = getcwd();

	//Получаем путь
	if ($_GET['path']) $path = $_GET['path'];

	//Запрашиваем содержимое
	$glob = glob("$path/*");

	$content['title'] = 'Файловый менеджер';

	list($dir, $file) = [[],[]];
	foreach ($glob as $filename)
	{
		$info = stat($filename);

		$element['path'] 	= $filename;
		$element['head'] 	= basename2($filename);
		$element['icon'] 	= icon_setter($filename, $APP->config->get()['patterns']);
		$element['load']	= 'admin/app/filemanager/file_download.php?path='.$filename;
		$element['ctime'] 	= date('d.m.Y H:i:s', $info['ctime']);
		$element['isdir'] 	= is_dir ($filename);
		$element['isfile'] 	= is_file($filename);


		if ($element['isdir'])
		{
			$element['link'] 	= 'admin/app/filemanager?path='.$path.'/'.basename($filename);
			$dir[] = $element;
		}
		if ($element['isfile'])
		{
			$element['link'] 	= 'admin/app/codeeditor?file='.$path.'/'.basename($filename);
			$file[] = $element;
		}

		//~ echo $element['link'] ; var_dump( $element['isfile']  );
		//echo "$filename размер " . filesize($filename) . "\n";
	}
	//Объединям (что бы директории были первыми в списке, а потом файлы)
	$content['folder'] = array_merge((array) $dir, (array) $file);
	$content['menu']['folders'] = $APP->config->get()['folders'];

	$content['path'] = $path;

	//Кнопка назад
	$buffer = (array) explode('/', $path);
	array_pop($buffer);
	$content['menu']['buttons']['back']['link'] = 'admin/app/filemanager?path='.implode('/', $buffer);


	//Возвращает иконки, соответствующие расширению и типу файла
	function icon_setter($filename, $patterns)
	{
		if (is_dir($filename)) return 'fa fa-folder';

		$ext = pathinfo($filename);
		$ext = $ext['extension'];

		if ( isset($patterns[$ext]) ) return $patterns[$ext];
		return 'fa fa-file';
	}

	//Возвращает basename (написана из-за ошибки в работе стоковой функции basename с русскими буквами)
	function basename2($path)
	{
        return substr(strrchr($path, "/"), 1);
    }

	//~ $themelink = $APP->url->home()."views/admin/";
	$APP->template->file('admin/app/file_manager/file_manager.html')->display($content);
