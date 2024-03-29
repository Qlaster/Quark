<?php

	$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);

	//Получаем список модулей (фасадов и моделей)
	//~ $units_path = $APP->__facades();

	$content['title'] = 'Установленные пакеты composer';

	//~ $vendorDir = $APP->core_config['path_vendor']['path'];
	$vendorDir = $_ENV['vendor']['path'];
	$vendors  = $APP->utils->files->dirListing($vendorDir);

	//Проходимся по доступным вендорам, заглядывая в пакеты
	foreach ($vendors as $_vendor)
		foreach ((array)$APP->utils->files->dirListing($vendorDir.DIRECTORY_SEPARATOR.$_vendor) as $_packege)
		{
			//Сгенерируем ссылку на описание пакета
			$composerJson = $vendorDir.DIRECTORY_SEPARATOR.$_vendor.DIRECTORY_SEPARATOR.$_packege.DIRECTORY_SEPARATOR."composer.json";
			if (!file_exists($composerJson)) continue;
			//Подготовим информацию о пакете
			$_packegeHead = json_decode(file_get_contents($composerJson), true);
			$_packegeHead['createdate'] = date('d.m.Y', filemtime($composerJson));
			$packages[$_vendor][$_packege] = $_packegeHead;
		}

	$content['packages']['head'] = 'Установленые пакеты';
	$content['packages']['list'] = $packages;
	$content['packages']['info'] = "Установить дополнительные пакеты можно через <b> composer </b> в директорию <b> $vendorDir </b> или добавить их туда явно";


	$APP->template->file('admin/components/packages.html')->display($content);
