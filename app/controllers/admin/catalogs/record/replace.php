<?php
/*
 * Catalog record replace.php
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

	try
	{
		if (!$_GET['catalog']) throw new Exception("Не указан каталог", 102);

		//Предварительная проверка целостности файлов
		foreach	($_FILES as $field => $_filerec)
			foreach ($_filerec['error'] as $error)
			{
				//~ if (!$file['tmp_name'])
				//~ {
					//~ unset($_FILES[$field]);
					//~ continue;
				//~ }
				if ($error != 0) exit('Ошибка при загрузке файла');
				$sendFiles = true;
			}

		$catalog = $APP->catalog->get($_GET['catalog']);

		if ($sendFiles ?? false)
		{
			//Проверим наличие директорий
			$folder = $catalog['folder'] ?? $APP->catalog->config()['upload']['folder'];
			if (!$folder) exit('Не найдена директория для ресурсов');

			if (!$catalog['folder'])
				$folder .= DIRECTORY_SEPARATOR.$_GET['catalog'];
			if (!file_exists($folder) and !mkdir($folder, 0777, true)) exit('Не удалось создать служебную директорию');
		}



		$APP->catalog->items($_GET['catalog'])->beginTransaction();
		if ($_POST['id'])
		{
			$APP->catalog->items($_GET['catalog'])->where(['id'=>$_POST['id']])->update($_POST);
		}
		else
		{
			unset($_POST['id']);
			$_POST['id'] = $APP->catalog->items($_GET['catalog'])->insert($_POST)->lastInsertId();
		}


		//Перемещаем файлики
		foreach	($_FILES as $field => $files)
		{
			if (!file_exists($uploadDIR = $folder.DIRECTORY_SEPARATOR.$_POST['id']) && !mkdir($uploadDIR, 0777, true))
				exit("Не удалось обработать файлы (ошибка записи)");

			$filelist = [];

			foreach ($APP->utils->files->reArrayFiles($files) as &$file)
			{
				//Проверяем конфигурацию, на требование переименовывать файлы
				$filelist[] = filter_var($APP->catalog->config()['upload']['renaming'], FILTER_VALIDATE_BOOLEAN)
					? $filename = $uploadDIR.DIRECTORY_SEPARATOR."$field-".uniqid().'.'.pathinfo($file['name'], PATHINFO_EXTENSION)
					: $filename = $uploadDIR.DIRECTORY_SEPARATOR.$file['name'];

				//Переместим файл в целевой каталог
				if (! move_uploaded_file($file['tmp_name'], $filename) )
					exit("Не удалось обработать файлы (ошибка записи): $filename");
			}


			//~ $filename .= DIRECTORY_SEPARATOR."$field-".uniqid().'.'.pathinfo($file['name'], PATHINFO_EXTENSION);

			//~ //Переместим файл в целевой каталог
			//~ if (! move_uploaded_file($file['tmp_name'], $filename) )
				//~ exit("Не удалось обработать файлы (ошибка записи): $filename");

			//~ print_r([$field=>implode("\r", $filelist)]);

			//Обновим свежеиспеченную запись полями загруженых файлов
			$APP->catalog->items($_GET['catalog'])->where(['id'=>$_POST['id']])->update([$field=>implode("\r", $filelist)]);
		}

		if (!$APP->catalog->items($_GET['catalog'])->Commit())
			exit("Не удалось добавить запись");

		echo "OK";
		return ['id'=>$_POST['id']];
	}
	catch (Exception $e)
	{
		echo 'Ошибка: ',  $e->getMessage(), "\n";
	}


