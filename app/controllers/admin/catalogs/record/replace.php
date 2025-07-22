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

	//~ print_r($_POST);
	//~ print_r($_FILES); die;


	try
	{
		if (!$_GET['catalog']) throw new Exception("Не указан каталог", 102);

		//Запросим переданные файлы
		$FILES = $APP->utils->files->uploadList();

		//Получим каталог
		$catalog = $APP->catalog->get($_GET['catalog']);


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
		foreach	($FILES as $field => $files)
		{
			$folder = createCatalogDirectory($catalog, $APP->catalog->config());

			if (!file_exists($uploadDIR = $folder.DIRECTORY_SEPARATOR.$_POST['id'].DIRECTORY_SEPARATOR.$field) && !mkdir($uploadDIR, 0777, true))
				exit("Не удалось обработать файлы (ошибка записи)");

			$filelist = [];

			foreach ($files as $findex => &$file)
			{
				if (!$file['name'])
				{
					unset($files[$findex]);
					continue;
				}
				if ($file['error'] != 0) exit('Ошибка при загрузке файла'); unset($file['error']);


				$file['filename'] = $filename = filter_var($APP->catalog->config()['upload']['renaming'], FILTER_VALIDATE_BOOLEAN)
					? $uploadDIR.DIRECTORY_SEPARATOR."$field-".uniqid().'.'.pathinfo($file['name'], PATHINFO_EXTENSION)
					: $uploadDIR.DIRECTORY_SEPARATOR.$file['name'];

				//Переместим файл в целевой каталог
				if (! move_uploaded_file($file['tmp_name'], $filename) )
					exit("Не удалось обработать файлы (ошибка записи): $filename");

				unset($file['tmp_name']);
			}

			//Если фалов на добавление нет - пропускаем итерацию
			if (!$files) continue;

			//Обновим свежеиспеченную запись полями загруженых файлов
			if ($catalog['field'][$field]['type'] == 'files')
			{//Если тип files - набор файлов, то реализуем другую обработку, с использование коллекций файлов
				$processedFiles = $APP->catalog->items($_GET['catalog'])->where(['id'=>$_POST['id']])->select()[0][$field];
				foreach ($files as $newFile) $processedFiles[md5($newFile['name'])] = $newFile;
				$APP->catalog->items($_GET['catalog'])->where(['id'=>$_POST['id']])->update([$field=>$processedFiles]);
			}
			else
				$APP->catalog->items($_GET['catalog'])->where(['id'=>$_POST['id']])->update([$field=>current($files)]);
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






	function createCatalogDirectory($catalog, $config)
	{
		//Проверим наличие директорий
		$folder = $catalog['folder'] ?? $config['upload']['folder'];
		if (!$folder) exit('Не найдена директория для ресурсов');

		if (!$catalog['folder'])
			$folder .= DIRECTORY_SEPARATOR.$catalog['name'];
		if (!file_exists($folder) and !mkdir($folder, 0777, true)) throw new Exception("Не удалось создать служебную директорию", 103);

		return $folder;
	}

