<?php

	//Статика сообщений — компаньон-ini [view] + перевод
	$M = $APP->l10n->translate(array_replace_recursive([], (array) $APP->config->get()['view']))['messages'];

	//Защита от загрузки файлов вне разрешённых директорий
	$path = $APP->files->jailPath($_POST['path'] ?? '');
	if (!$path)
		throw new Exception($M['deny']['text']);


	$APP->files->uploadMove($path.DIRECTORY_SEPARATOR, filter_var($_POST['uniq'], FILTER_VALIDATE_BOOLEAN), '');
