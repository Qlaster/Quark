<?php


	$content['catalog'] = $APP->catalog->get($_GET['catalog']);

	$content['catalog']['field'] = $APP->catalog->fields($_GET['catalog']);

	if ($_GET['id'])
		$record = $APP->catalog->items($_GET['catalog'])->where(['id'=>$_GET['id']])->select();

	$content['record'] = current((array) ($record??[]));


	//Отрисуем
	$APP->template->file('admin/catalogs/frame.edit.html')->display($content);

