<?php

	//~ print_r($_GET); die;

	if ($_GET['id'])
		$record = $APP->catalog->items($_GET['catalog'])->where(['id'=>$_GET['id']])->select();
	$content['record'] = current((array)$record);
	$content['catalog'] = $APP->catalog->get($_GET['catalog']);

	//~ print_r($content); die;

	//Отрисуем
	$APP->template->file('admin/catalogs/edit-frame.html')->display($content);

