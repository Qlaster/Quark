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

	if (!$name = $_GET['name'])
	{
		//Название каталога не передано, сделаем редирект на список
		header('Location: index');
		exit;
	}

	//Загрузим каталог
	$content['catalog'] = $APP->catalog->get($name);
	$content['catalog']['name'] = $name;
	//Получим актуальные поля
	$content['catalog']['field'] = $content['catalog']['field'] ?? $APP->catalog->fields($name);


	//Выгрузим данные (для оптимизации памяти, получим только те поля, которые требуются для каталога)
	//~ $content['catalog']['list']  = $APP->catalog->items($name)->select( array_keys($content['catalog']['field']) );
	$content['catalog']['list']  = $APP->catalog->view($name);


	//Отфильтруем по фактическим полям
	if ($content['catalog']['list'])
		$content['catalog']['field'] = array_intersect_key($content['catalog']['field'], current($content['catalog']['list']));


	//Напишем заголовок
	$content['title'] = $content['catalog']['head'];

	//Отрисуем
	$APP->template->file('admin/catalogs/view.html')->display($content);

