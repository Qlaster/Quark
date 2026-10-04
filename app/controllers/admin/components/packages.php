<?php

	$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);

	//Статическая GUI-структура — компаньон-ini [view] + перевод
	$cfg = $APP->config->get();
	$content = array_replace_recursive($content, (array) $cfg['view']);
	$content = $APP->l10n->translate($content);

	$content['title'] = $content['title']['head'];

	//Получаем список модулей (фасадов и моделей)
	//~ $units_path = $APP->__facades();

	//~ $vendorDir = $APP->core_config['path_vendor']['path'];
	$vendorDir = $_ENV['vendor']['path'];
	$vendors  = $APP->files->listingDir($vendorDir);

	//Проходимся по доступным вендорам, заглядывая в пакеты
	foreach ($vendors as $_vendor)
		foreach ((array)$APP->files->listingDir($vendorDir.DIRECTORY_SEPARATOR.$_vendor) as $_packege)
		{
			//Сгенерируем ссылку на описание пакета
			$composerJson = $vendorDir.DIRECTORY_SEPARATOR.$_vendor.DIRECTORY_SEPARATOR.$_packege.DIRECTORY_SEPARATOR."composer.json";
			if (!file_exists($composerJson)) continue;
			//Подготовим информацию о пакете
			$_packegeHead = json_decode(file_get_contents($composerJson), true);
			$_packegeHead['createdate'] = date('d.m.Y', filemtime($composerJson));
			$packages[$_vendor][$_packege] = $_packegeHead;
		}

	$content['packages']['list'] = $packages;
	$content['packages']['info'] = sprintf($content['packages']['info'], $vendorDir);


	$APP->template->file('admin/components/packages.html')->display($content);
