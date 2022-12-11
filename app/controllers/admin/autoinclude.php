<?php

/*
 * QyberTech
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

	// Добавлять сообщения обо всех ошибках, кроме E_NOTICE
	error_reporting(E_ALL & ~E_NOTICE);

	//Если функция уже доступна - вызовем ее
	if ($autoconstruct) return $autoconstruct($APP);


	$autoconstruct = function ($APP)
	{
		//============================================================================================================================
		//			LOCK PAGE
		//============================================================================================================================
		//Если сессия залочена - маршрутим на другую страницу
		if (isset($_SESSION['lock']))
		{
			header('Location: '.$APP->url->home().'admin/options/users/lock');
			exit();
		}

		//============================================================================================================================
		//			ПРОВЕРКА НА АВТОРИЗАЦИЮ
		//============================================================================================================================
		if (! $APP->user->logged() and (!in_array($APP->url->page(), ['admin/login', 'admin/options/users/lock'])))
		{
			header('Location: '.$APP->url->home().'admin/login');
			exit();
		}

		//============================================================================================================================
		//			А ПРАВ ДОСТАТОЧНО?
		//============================================================================================================================
		if ($APP->user->denied( $APP->controller->realpath( $APP->url->page() ) ))
		{
			header('Location: '.$APP->url->home().'admin/options/users/deny');
			exit();
		}


		//============================================================================================================================
		//			ГЛАВНОЕ МЕНЮ
		//============================================================================================================================

		//Загружаем главное меню
		$menu = $APP->object->collection('admin')->get('mainmenu');

		//Подключаем нужное языковое меню
		$content['nav']['main'] = $menu['ru'];

		//Указываем пункт меню, который раскрыть
		$page = $APP->url->page();
		$page = explode('/', $page);
		$page = array_filter($page);

		//Выделение элемента меню
		if ($content['nav']['main']['list'][$page[1]])
		{
			//Если структура меню очевидна из ключей
			$content['nav']['main']['list'][$page[1]]['active'] = true;
			//Если дочерний элемент существует - то тоже выделим
			if (isset($content['nav']['main']['list'][$page[1]]['list'][$page[2]]))
				$content['nav']['main']['list'][$page[1]]['list'][$page[2]]['active'] = true;
		}
		else
		{
			//Если нужно пробежаться по меню и найти страницу по ссылке
			foreach ($content['nav']['main']['list'] as $key => &$mainSection)
				if ($mainSection['list'])
					foreach ($mainSection['list'] as $subkey => &$item)
						if ($item['link'] == $APP->url->page())
						{
							$item['active'] = true;
							$mainSection['active'] = true;
						}
		}

		//============================================================================================================================
		//			Удалим из главного меню запрещенные контроллеры
		//============================================================================================================================
		foreach ($content['nav']['main']['list'] as $key => &$mainSection)
			foreach ((array)$mainSection['list'] as $subkey => &$item)
				if ($APP->user->denied( $APP->controller->realpath($item['link']) ))
				{
					unset($mainSection['list'][$subkey]);
					if (!$mainSection['list']) unset($content['nav']['main']['list'][$key]);
				}

		//============================================================================================================================
		//			ПУТЕВОЕ ВСПОМОГАТЕЛЬНОЕ МЕНЮ
		//============================================================================================================================

		//Получаем список веток пути
		$path = explode('/', $APP->url->page());
		$path = array_filter($path);
		//Создаем их представление
		foreach ($path as $key => &$value)
		{
			$tmppathelem .= "$value/";
			$tmp['list'][$key]['head'] = $value;
			if ($APP->controller->exists($tmppathelem)) $tmp['list'][$key]['link'] = $tmppathelem;
		}

		switch (count($page))
		{
			case 2:
				$tmp['head'] = $content['nav']['main']['list'][$page[1]]['head'];
				break;
			case 3:
				$tmp['head'] = $content['nav']['main']['list'][$page[1]]['list'][$page[2]]['head'];
				break;
		}

		//$tmp['head'] = $content['nav']['main']['list'][$page[1]]['list'][$page[2]]['head'];
		//Прикрепляем этот путь к контенту ввиде побочного меню
		$content['nav']['path'] = $tmp;


		//============================================================================================================================
		//			HTML BASE ADMIN PATH
		//============================================================================================================================
		//Прикрепляем базовую страницу
		$content['base'] = $APP->url->home();
		$APP->template->base_html = $APP->url->home();


		//============================================================================================================================
		//			CURRENT PROFILE
		//============================================================================================================================
		//Загружаем меню управления профилем
		$menu = $APP->object->collection('admin')->get('profilemenu');
		$content['nav']['profile'] = $menu['ru'];
		$content['profile'] = $APP->user->logged();

		//============================================================================================================================
		//			FOOTER
		//============================================================================================================================
	    $host = $APP->url->host();
		$spacedisk = disk_free_space(__DIR__)/1024/1024;
		$spacedisk = $spacedisk > 1024 ? round($spacedisk/1024, 2).' Gb' : round($spacedisk, 2).' Mb';

	    $content['footer']['head'] = "Свободное пространство: $spacedisk";
		$content['footer']['text'] = "<strong> $host </strong> - Admin panel. Copyright " . date("Y");


		return $content;
	};



	return $autoconstruct($APP);

