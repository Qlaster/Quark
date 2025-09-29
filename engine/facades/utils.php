<?php

	/*
	 * utils
	 *
	 * Различные утилиты и механизмы разнообразного назначения
	 *
	 * Version 1.0
	 * Copyright 2022



	 Построить иерархию файлов директории
	 $APP->utils->files->tree($dir, $mask=null)

	 Список файлов во всех поддиректориях одномерным массивом
	 $APP->utils->files->listing($dir, $mask=null)

	 Список файлов во всех поддиректориях сгруппированные по коллекциям
	 $APP->utils->files->collection($dir, $mask=null)

	*/

	namespace App\Facade;

	# ---------------------------------------------------------------- #
	#                 РЕАЛИЗАЦИЯ   ИНТЕРФЕЙСА                          #
	# ---------------------------------------------------------------- #
	class Utils
	{
		public $files;

		function __construct()
		{
			$this->files = new QFilesystemTools;
		}

		function runtime()
		{
			return round(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 5);
			//~ echo 'Время выполнения скрипта: '.round(microtime(true) - $start, 4).' сек.';
		}
	}










	# ---------------------------------------------------------------- #
	#                  УПРАВЛЕНИЕ ФАЙЛОВОЙ СИСТЕМОЙ                    #
	# ---------------------------------------------------------------- #
	class QFilesystemTools
	{
		public $umask = 0776;

		//Дерево файлов
		function tree($start, $mask=null)
		{
			$files = array();

			error_reporting(0);
			$handle = opendir($start);
			//~ error_reporting(E_ALL & ~E_NOTICE);
			if (!$handle) return $files;

			while (false !== ($file = readdir($handle)))
			{

				if ($file != '.' && $file != '..' && $file != '')
				{
					if (is_dir($start.DIRECTORY_SEPARATOR.$file))
					{
						$dir = $this->tree($start.DIRECTORY_SEPARATOR.$file, $mask);
						//Если в директории есть файлы - показыаем
						if (count($dir) > 0) $files[$file] = $dir;
					}
					else
					{
						//Если попадает под маску - добавляем
						if ($mask !== null)
						{
							if (fnmatch($mask, $file)) $files[$file] = $file;
						}
						else
						{
							$files[$file] = $file;
						}
					}
				}
			}
			krsort($files, SORT_STRING);
			closedir($handle);
			return $files;
		}


		//Превращение массива со списком файлов в дерево
		function listingToTree(array $listing = [], $delimiter=DIRECTORY_SEPARATOR)
		{
			$result = [];
			foreach ($listing as $_item)
			{
				//Разложим путь на массив и обратим порядок в обратную сторону
				$_array = array_reverse( explode($delimiter, $_item) );

				$buffer = [];
				$prev_key = null;
				//Пройдемся по пути и выберем все элементы
				foreach ($_array as $_key => $_value)
				{
					//Если это последний элемент - вложим информацию, иначе просто создадим еще 1 уровень вложения
					$buffer[$_value] = $buffer ? $buffer : $_item;
					//~ $buffer[$_value] = $buffer ? $buffer : (object) $_item;
					//~ $buffer[$_value] = $buffer;
					//Удалим прошлую итерацию, т.к. если мы здесь - она еще не завершена
					if ($prev_key !== null) unset($buffer[$prev_key]);
					//Сохраним ключ на родительский элемент
					$prev_key = $_value;
				}
				//Объединима новую информацию с прежними сведениями
				$result = array_merge_recursive($result, $buffer);
			}
			return (array) $result;
		}


		function listingC($path, $mask=null)
		{
			$rii = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path));
			$files = array();
			foreach ($rii as $file)
				if (!$file->isDir())
					if (!$mask)
						$files[] = $file->getPathname();
					else
						fnmatch($mask, $file->getFilename()) ? $files[] = $file->getPathname() : null;
			return $files;

		}

		//Список файлов во всех поддиректориях одномерным массивом
		function listing($folder, $mask=null, &$all_files=array())
		{
			$fp=opendir($folder);
			while($cv_file=readdir($fp))
			{
				if(is_file($folder.DIRECTORY_SEPARATOR.$cv_file))
				{
					if ($mask !== null)
					{
						if (fnmatch($mask, $folder.DIRECTORY_SEPARATOR.$cv_file)) $all_files[]=$folder.DIRECTORY_SEPARATOR.$cv_file;
					}
					else
					{
						$all_files[]=$folder.DIRECTORY_SEPARATOR.$cv_file;
					}
				}
				elseif ($cv_file!="." && $cv_file!=".." && is_dir($folder.DIRECTORY_SEPARATOR.$cv_file))
				{
					$this->listing($folder.DIRECTORY_SEPARATOR.$cv_file, $mask, $all_files);
				}
			}
			closedir($fp);
			return $all_files;
		}


		//Список папок в каталоге. (только папок, без учета файлов)
		function dirListing($dir)
		{
			$handle_content = opendir ($dir); //Открываем директорию
			if ($dir != "") $dir = $dir. "/"; //Если нам подсунули не пустую папку, то ставим слеш в конце

			while ($cat = readdir($handle_content))
				{
					if (is_dir($dir.$cat) and ($cat != '.') and ($cat != '..'))
					{
						$result[] = $cat;
					}
				}
			closedir($handle_content);
			return $result ?? [];
		}

		//Безопасная проверка на наличие директории (не проканает ../../ ну и прочие). Проверка проводится дословно.
		function exists($dir, $name)
		{
			$buf = $this->dirListing($dir);
			if (count($buf) == 0) return false; //Если массив пуст
			if (array_search($name, $buf) !== false) return true;
		}

		//Список файлов во всех поддиректориях сгруппированные по коллекциям
		function collection($folder, $mask=null)
		{
			$all_files = $this->listing($folder);

			foreach ($all_files as $fullfilename)
			{
				$info = pathinfo($fullfilename);
				if (isset($mask))
				{
					if (fnmatch($mask, $info['basename']))
						$result[$info['dirname']][] = $info['basename'];
				}
				else
				{
					$result[$info['dirname']][] = $info['basename'];
				}
			}

			return (array) $result;
		}



		function remove($path)
		{
			if (is_file($path)) return unlink($path);
			if (is_dir($path))
			{
				foreach(scandir($path) as $p) if (($p!='.') && ($p!='..'))
					$this->remove($path.DIRECTORY_SEPARATOR.$p);
				return rmdir($path);
			}

			return false;
		}

		//Возвращает basename (написана из-за ошибки в работе стоковой функции basename с русскими буквами)
		function basename($path)
		{
			return substr(strrchr($path, DIRECTORY_SEPARATOR), 1);
		}



		/*
		 *
		 * name: Меняет структуру $_FILES на более удобную
		 * @param $_FILES
		 * @return FILES structures
		 *
		 */
		function reArrayFiles($file_post)
		{
			//Если конвертация не требуется - выходим
			if (! is_array($file_post['name']) ) return array(0=>$file_post);

			$file_ary = array();
			$file_count = count($file_post['name']);
			$file_keys = array_keys($file_post);

			for ($i=0; $i<$file_count; $i++)
			{
				foreach ($file_keys as $key)
				{
					$file_ary[$i][$key] = $file_post[$key][$i];
				}
			}

			return $file_ary;
		}


		/*
		 *
		 * name: Список файлов, переданных на загрузку
		 * @param Если указан tree - прикрепит к каждому полю ввода список файлов. Если false - создаст "плосский" список с перечислением файлов
		 * @return FILES structures
		 *
		 */
		function uploadList($tree=true)
		{
			$result = [];

			if ($tree)
			{
				foreach ($_FILES as $_key => $_filelist)
					$result[$_key] = $this->reArrayFiles($_filelist);
			}
			else
			{
				foreach ($_FILES as $_key => $_filelist)
					$result = array_merge($result, $this->reArrayFiles($_filelist));
			}
			return (array) $result;
		}


		/*
		 *
		 * name: Переместить единичный загруженный файл в указанную директорию
		 * @param
		 * @return FILES structures
		 *
		 */
		function uploadMoveSingleFile(&$tmpFileRecord, $targetDir, $prefix="content_", $filename=null)
		{
			//Получим расширение файла
			$ext = pathinfo($tmpFileRecord['name'], PATHINFO_EXTENSION);

			if (!file_exists($targetDir)) mkdir($targetDir, $this->umask, true);

			//Сгенерируем новое имя файла в целевой директории
			//~ $new_filename = tempnam($targetDir, $prefix); 	// алгоритм 1
			if (!$filename)
			{
				do
				{
					$new_filename = "$targetDir/$prefix".uniqid().".$ext";	//алгоритм 2
				} while (file_exists($new_filename));
			}
			else
			{
				//~ $new_filename = "$targetDir/$filename.$ext";
				$new_filename = "$targetDir/$filename";
			}

			//Переместим свежий файл в целевую директорию
			move_uploaded_file($tmpFileRecord['tmp_name'], $new_filename); //$_SERVER['CONTEXT_DOCUMENT_ROOT']

			//Установим дефолтные права
			chmod($new_filename, $this->umask);

			$tmpFileRecord['new_name'] = $new_filename;
			//Вернем информацию о перемещении в результат
			//~ return $new_filename;
			return $tmpFileRecord;
		}

		/*
		 *
		 * name: Переместить все загружаемые файлы в указанную директорию
		 * @param
		 * @return FILES structures
		 *
		 */
		function uploadMove($targetDir, $uniqueName=true, $prefix="content_")
		{
			//Получим список загружаемых файлов
			$filesblocks = $this->uploadList();

			//Проверим существование директории и если ее нет - создадим
			if (! file_exists($targetDir)) mkdir($targetDir, $this->umask, true);

			foreach ($filesblocks as $_name => &$_filelist)
			{
				if (is_array($_filelist))
				{
					foreach	($_filelist as $_key => &$_file)
					{
						if (!is_array($_file))	continue;
						$fileName = $uniqueName ? null : $_file['name'];
						if ($_file['error'] == UPLOAD_ERR_OK)
							$_file = $this->uploadMoveSingleFile($_file, $targetDir, $prefix, $fileName);
					}
						//~ $_file['new_file'] = $this->uploadMoveSingleFile($_file['tmp_name'], $targetDir);
				}
				else
				{
					//~ if (($_filelist['error']) and ($_filelist['error'] == UPLOAD_ERR_OK))
					$fileName = $uniqueName ? null : $_filelist['name'];
					if ($_filelist['error'] == UPLOAD_ERR_OK)
						$this->uploadMoveSingleFile($_filelist, $targetDir, $prefix, $fileName);
					//~ $_filelist['new_file'] = $this->uploadMoveSingleFile($_filelist['tmp_name'], $targetDir);
				}
			}
			return (array) $filesblocks;
		}

		/*
		 * Форматирует размер в удобочитаемый вид с использованием подходящих единиц измерения.
		 *
		 * @param int $size Исходный размер в байтах.
		 * @param array $units Массив единиц измерения (по умолчанию: байт, КБ, МБ, ГБ, ТБ).
		 * @return string Отформатированное строковое представление размера с выбранной единицей измерения.
		 */
		function sizeFormatter(int $size, $units=['байт', 'КБ', 'МБ', 'ГБ', 'ТБ'])
		{
			$units = ['байт', 'КБ', 'МБ', 'ГБ', 'ТБ'];
			$unitIndex = 0;

			while ($size >= 1024 && $unitIndex < count($units) - 1) {
				$size /= 1024;
				$unitIndex++;
			}

			return str_replace(".00", "", number_format($size, 2) . ' ' . $units[$unitIndex]);
		}


	}




	# ---------------------------------------------------------------- #
	# --------------[ СОЗДАЕМ И ПОДКЛЮЧАЕМ ИНТЕРФЕЙС ]---------------- #
	# ---------------------------------------------------------------- #

	return new Utils;




