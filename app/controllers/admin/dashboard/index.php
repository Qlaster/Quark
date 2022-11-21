<?php

/*
 * QEXT DASHBOARD
 *
 * Copyright 2015 Владимир <vladimir@ASUS>
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



	$content = $APP->controller->run('admin/autoinclude', $APP);




	$content['widgets']['list']['page']['head'] = 'Страниц';
	$content['widgets']['list']['page']['text'] = $APP->page->count();

	$content['widgets']['list']['users']['head'] = 'Пользователей';
	$content['widgets']['list']['users']['text'] = count($APP->user->all());

	$content['widgets']['list']['db']['head'] = 'Баз данных';
	$content['widgets']['list']['db']['text'] = count($APP->db->listing());

	$content['graph']['head'] = 'Статистика посещаемости ресурса за последние дни';
	$content['graph']['text'] = '<label class="label label-primary">Зеленым</label> отмечены уникальные пользователи, <label class="label label-default">серым</label> - объем страниц, который они посетили';



	//~ $themelink = $APP->url->home()."views/admin/";
	$APP->template->file('admin/dashboard.html')->display($content);
