<?php
/*
 * save about.php
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
	//Язык панели — отдельно: пишется в engine/facades/l10n.ini (translate.lang),
	//а не в объект about. Валидируем по списку существующих локалей
	$syslang = trim($_POST['syslang'] ?? '');
	unset($_POST['syslang']);

	if ($syslang !== '' and in_array($syslang, array_keys((array) $APP->l10n->langs())))
	{
		$l10nFile = $_ENV['facades']['app'].'/l10n.ini';
		$l10nCfg  = (array) $APP->config->get($l10nFile);
		$l10nCfg['translate']['lang'] = $syslang;
		$APP->config->set($l10nCfg, $l10nFile);
	}

	$_POST['poster'] = 'public/images/poster.png';

	echo $APP->objects->collection('admin')->set('about', $_POST) ? "OK" : "";

