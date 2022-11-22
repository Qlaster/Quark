<?php
/*
 * about.php
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

	$content = $APP->controller->run('admin/autoinclude', $APP);

	//Если нам передали постер
	if ($_FILES)
	{
		if ($_FILES['poster']['error'] === 0)
		{
			move_uploaded_file($_FILES['poster']['tmp_name'], 'public/images/poster.png');
		}
	}

	$content['form_info'] = $APP->objects->collection('admin')->get('about');
	$content['poster']['link'] = 'public/images/poster.png?';

	$APP->template->file('admin/options/about.html')->display($content);




