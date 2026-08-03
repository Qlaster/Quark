<?php


	$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);

	//Файлы, который нужно открыть (только внутри разрешённых директорий)
	if (($f = $APP->files->jailPath($_GET['file'] ?? '')) and is_file($f)) echo file_get_contents($f);
