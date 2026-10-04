<?php


	$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);

	//Статическая GUI-структура — компаньон-ini [view] + перевод
	$cfg = $APP->config->get();
	$content = array_replace_recursive($content, (array) $cfg['view']);
	$content = $APP->l10n->translate($content);

	//Файлы, который нужно открыть (только внутри разрешённых директорий)
	$path = $APP->files->jailPath($_GET['path'] ?? '');
	if (!$path or !file_exists($path))
	{
		echo sprintf($content['messages']['notfound']['text'], $_GET['path']);
		return;
	}
	$info = stat($path);

	//Имя
	$info['name'] 	= basename($path);
	//Full path
	$info['path'] 	= $path;
	//Создан
	$info['ctime'] 	= date('d.m.Y H:i:s', $info['ctime']);
	//Изменен
	$info['mtime'] 	= date('d.m.Y H:i:s', $info['mtime']);
	//Чтение/запись
	$info['write'] 	= is_writable($path);
	//Это директория?
	$info['dir'] 	= is_dir($path);

	//
	$sign = $content['units']['list'];

	$i = 0;
	while ($info['size'] > 1024)
	{
		$info['size'] = $info['size'] / 1024;
		$i++;
	}
	$info['size'] = round($info['size'], 2) .$sign[(int)$i];




	$content['info'] 	= $info;
	//~ $themelink = $APP->url->home()."views/admin/";

	$html = 'admin/tools/file-manager/file-manager-info.html';
	if ($_GET['version'] == 'min') $html = 'admin/tools/file-manager/file-manager-info-min.html';



	$APP->template->file($html)->display($content);

	exit;
