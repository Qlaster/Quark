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

	//Название каталога не передано, сделаем редирект на список
	if (!$name = $_GET['name']) header('Location: index') && exit;

	//Напишем заголовок
	$content['title'] = $content['catalog']['head'];
	//Action для получения данных каталога
	$content['catalog']['link'] = "admin/catalogs/view/table?name=$name";

	//Отрисуем
	$APP->template->file('admin/catalogs/view.html')->display($content);

