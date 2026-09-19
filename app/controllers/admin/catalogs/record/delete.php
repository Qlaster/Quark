<?php
/*
 * Catalog recoed replace.php
 *
 * Copyright 2026 vladimir <vladimir@MacBookAir>
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

	try
	{
		$id = filter_var($_REQUEST['id'], FILTER_VALIDATE_INT);
		if (!$id) throw new Exception("Не указан ID", 101);
		if (!$_REQUEST['catalog']) throw new Exception("Не указан каталог", 102);

		//Права доступа к каталогу для текущего пользователя.
		//Гость (logged() === false) получает скоуп субъекта null — совпадают только *-маски
		$user = $APP->user->logged();
		$ACC  = $APP->catalog->access($_REQUEST['catalog'])
			->as($user ? $user['login'] : null);

		//Операция delete разрешена этому субъекту?
		if (!$ACC['delete'])
			throw new Exception("Удаление записей в этом каталоге запрещено", 403);

		//Удаляем. $ACC['where']['delete'] — sql-фрагмент скоупа:
		//записи вне разрешённого where просто не попадут под DELETE
		$APP->catalog->items($_REQUEST['catalog'])
			->where($ACC['where']['delete'])
			->where(['id'=>$id])
			->delete();

		//Удаляем только каталог внутри catalogDIR — id строго числовой, без обхода пути
		if ($catalogDIR = $APP->catalog->get($_REQUEST['catalog'])['folder'])
			$APP->files->remove($catalogDIR.DIRECTORY_SEPARATOR.$id);
		echo "OK";
	}
	catch (Exception $e)
	{
		echo 'Ошибка: ',  $e->getMessage(), "\n";
	}


