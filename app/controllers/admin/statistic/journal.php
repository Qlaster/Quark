<?php

	$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);


	$content['title'] = "Журнал запросов";
	$content['journal']['head'] = "Журнал запросов";

	//Если не передали даты - установим сегодняшний день
	$content['datestart'] = $_GET['datestart'] ? $_GET['datestart'] : date('d.m.Y');
	$content['dateend']   = $_GET['dateend']   ? $_GET['dateend']   : date('d.m.Y');

	//~ $datestart = date('Y-m-d', strtotime($content['datestart']));
	//~ $dateend   = date('Y-m-d', strtotime($content['dateend']));
	//~ $datestart = $content['datestart'];
	//~ $dateend   = $content['dateend'];

	//Устанавливаем окно диапазона дат
	$APP->visits->shear($content['datestart'], $content['dateend'] );
	//~ $content['journal'] = $APP->visits->statistics();

	//Вычитаем историю посещения
	while ($record = $APP->visits->next())
	{
		$record['date'] = date("d.m.Y H:i:s", $record['time']);
		$record['uri']  = urldecode($record['uri']);
		$record['uri']  = str_replace('&', '<br>&', $record['uri']);
		$record['uri']  = strpos($record['uri'], '?') ? '<b>'.mb_str_replace_once('?', '</b>?', $record['uri']) : '<b>'.$record['uri'].'</b>';

		$record['mempeak'] .= " kb";
		$content['journal']['list'][] = $record;
	}

	$APP->template->file('admin/statistic/journal.html')->display($content);
