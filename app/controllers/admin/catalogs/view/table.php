<?php
/*
 * Catalog View
 *
 * Copyright 2022 vladimir <vladimir@MacBookAir>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-1301, USA.
 *
 *
 */

	$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);

	//Если название каталога не передано, тихонечко сбежим
	if (!$name = $_GET['name']) exit;

	//Права доступа к каталогу для текущего пользователя (гость → только *-маски)
	$user = $APP->user->logged();
	$ACC  = $APP->catalog->access($name)->as($user ? $user['login'] : null);

	//select разрешён субъекту? Если нет — таблица не отдаётся вовсе
	if (!$ACC['select']) exit("Просмотр этого каталога запрещён");

	$params = $_GET;
	$catalogCfg = $APP->catalog->get($name);

	//Ограничение записей: $ACC['where']['select'] — композитный sql-фрагмент
	//((allow) OR ...) AND NOT((deny) OR ...). params['where'] в items()
	//перекрывает events.view.where из конфига — поэтому склеиваем оба
	if ($where = $ACC['where']['select'])
	{
		$eventsWhere = $catalogCfg['events']['view']['where'] ?? null;
		$params['where'] = $eventsWhere ? "($eventsWhere) AND ($where)" : $where;
	}

	//Ограничение полей через штатный параметр column: view() передаст его
	//в select() — запрещённые колонки не уйдут из БД вовсе, а field-описания
	//view() пересечёт сам (array_intersect_key внутри).
	//id добавляем всегда — им таблица адресует записи для edit/delete.
	//Если пользователь уже просил ?column= — пересекаем с разрешённым;
	//также не забываем events.view.column из конфига (params его перекрывает)
	$declared = array_keys((array) $catalogCfg['field']);
	$allowed  = array_filter($declared, function($f) use ($ACC) {
		return $ACC->fieldAllowed('select', $f);
	});
	if ($allowed !== $declared)   //ограничения есть — подмешиваем column
	{
		$allowed[] = 'id';
		$base = $params['column'] ?: ($catalogCfg['events']['view']['column'] ?? '');
		$params['column'] = $base
			? implode(',', array_intersect(array_map('trim', explode(',', $base)), $allowed))
			: implode(',', $allowed);
	}

	$content['catalog'] = $APP->catalog->view($name, $params);

	// URL для автообновления — текущий запрос со всеми GET-параметрами
	$content['catalog']['uri'] = linker();

	$content['menu']['pages']['list'] = [];

	//Сконструируем [+ меню пагинации +], если у нас больше записей чем выводим
	if ($content['catalog']['count'] > count($content['catalog']['list']))
	{
		$_GET['offset'] = $_GET['offset'] ? (int) $_GET['offset'] : 0;

		//Сформируем оверлей страниц, для этого, подсмотрим конфиг
		list($range, $limit) = [$APP->catalog->config()['view']['range'], $content['catalog']['limit']];

		for ($page = 0; ($step = $page*$limit) < $content['catalog']['count']; $page++)
		{
			$content['menu']['pages']['list'][$page]['head']   = $page+1;
			$content['menu']['pages']['list'][$page]['link']   = linker(['offset'=>$step]);
			//~ $content['menu']['pages']['list'][$page]['active'] = ($_GET['offset']/$content['catalog']['limit']??1) == $page;
			$content['menu']['pages']['list'][$page]['active'] = $step==$_GET['offset'];
		}

		//Потом вычислим срез
		$startIndex = max(0, $_GET['offset']/$limit - floor($range / 2));
		$endIndex   = min(count($content['menu']['pages']['list'])-1, $startIndex + $range-1);

		// Корректируем начальный индекс, если нужно, чтобы получить ровно range элементов
		$startIndex = max(0, $endIndex - $range + 1);

		//Запомним максимальное количество страниц
		$maxPage = count($content['menu']['pages']['list']);

		// Вырезаем сегмент
		$content['menu']['pages']['list'] = array_slice($content['menu']['pages']['list'], $startIndex, $range);

		//Обрамляем кнопочками "вперед"/"назад"
		if ($startIndex > 0)
			array_unshift($content['menu']['pages']['list'], ['head'=>'❮', 'link'=>linker(['offset'=>$_GET['offset']-$limit])]); // Кнопка "назад"
		if ($endIndex < $maxPage-1)
			$content['menu']['pages']['list']['❯'] = ['head'=>'❯', 'link'=>linker(['offset'=>$_GET['offset']+$limit])]; // Кнопка "далее"
	}

	//Напишем заголовок
	$content['title'] = $content['catalog']['head'];

	//Форма отбора
	$content['form']['filter']['action']           = linker(['offset'=>0]);
	$content['form']['filter']['like']['value']    = $_GET['like'];
	$content['form']['filter']['orderby']['value'] = $_GET['orderby'];


	$content['catalog']['action']['edit']['icon']   = 'fa fa-pencil';
	$content['catalog']['action']['edit']['link']   = "admin/catalogs/edit";
	$content['catalog']['action']['delete']['icon'] = 'fa fa-trash';
	$content['catalog']['action']['delete']['link'] = "admin/catalogs/record/delete";
	//~ $content['catalog']['link'] = linker();

	//Отрисуем сортировку
	if ($_GET['orderby'] && list($column, $direction) = explode(' ', $_GET['orderby']))
		$content['catalog']['field'][$column]['icon'] = "fa fa-sort-amount-$direction";


	//Отрисуем
	$APP->template->file('admin/catalogs/frame.table.html')->display($content);




	//метод формирования ссылки по входящим параметрам
	function linker($params=[], $link="")
	{
		global $APP;
		$link   = $link ? "$link" : $APP->url->page();
		$params = array_merge($_GET, $params);
		foreach ($params as $name => &$_value) $_value = "$name=".urlencode($_value);
		return "$link?".implode('&', $params);
	}

