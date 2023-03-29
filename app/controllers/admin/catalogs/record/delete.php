<?php
/*
 * Catalog recoed replace.php
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
 *
 */

	if (!$_REQUEST['id']) throw new Exception("Не указан ID", 101);
	if (!$_REQUEST['catalog']) throw new Exception("Не указан каталог", 102);

	try
	{
		$APP->catalog->items($_REQUEST['catalog'])->where(['id'=>$_REQUEST['id']])->delete();

		if ($catalogDIR = $APP->catalog->get($_REQUEST['catalog'])['folder'])
			$APP->utils->files->remove($catalogDIR.DIRECTORY_SEPARATOR.$_REQUEST['id']);
		echo "OK";
	}
	catch (Exception $e)
	{
		echo 'Ошибка: ',  $e->getMessage(), "\n";
	}


