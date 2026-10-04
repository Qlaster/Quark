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



	$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);

	//Статическая структура страницы — компаньон index.ini, секция [view]
	$config  = $APP->config->get();
	$content = array_replace_recursive($content, (array) $config['view']);

	//Скалярные строки — через словарь [t] (промах → оригинал)
	$content['title'] = $APP->l10n->translate($content['title']);

	//Динамика — счётчики виджетов
	$content['widgets']['list']['page']['text']  = $APP->page->count();
	$content['widgets']['list']['users']['text'] = count($APP->user->all());
	$content['widgets']['list']['db']['text']    = count($APP->db->listing());

	//Локализация маркированных узлов (виджеты, граф, charts)
	$content = $APP->l10n->translate($content);

	//~ $themelink = $APP->url->home()."views/admin/";
	$APP->template->file('admin/dashboard.html')->display($content);
