<?php


	namespace App\Facade;

	# ---------------------------------------------------------------- #
	#                  УПРАВЛЕНИЕ ФАЙЛОВОЙ СИСТЕМОЙ                    #
	# ---------------------------------------------------------------- #
	class QFilesTools
	{
		public $umask = 0776;

		/**
		 * Построение дерева файлов и директорий.
		 * @param string $start Путь к начальной директории.
		 * @param string|null $mask Маска шаблона для фильтрации файлов (например, '*.txt').
		 * @return array Структура дерева файлов.
		 */
		public function tree(string $start, string $mask = null): array
		{
			$files = [];
			$handle = @opendir($start);
			if (!$handle) return $files;

			while (($file = readdir($handle)) !== false)
			{
				if ($file === '.' || $file === '..' || $file === '') continue;

				$fullPath = $start . DIRECTORY_SEPARATOR . $file;
				if (is_dir($fullPath))
				{
					$dir = $this->tree($fullPath, $mask);
					//Если в директории есть файлы - показыаем
					if (!empty($dir)) $files[$file] = $dir;
				} else
				{
					//Если попадает под маску - обрабатываем и добавляем
					if ($mask !== null)
					{
						if (fnmatch($mask, $file))	$files[$file] = $file;
					} else
					{
						$files[$file] = $file;
					}
				}
			}
			closedir($handle);
			krsort($files, SORT_STRING);
			return $files;
		}


		/**
		 * Получение списка всех файлов в каталоге и его подкаталогах (рекурсивно).
		 * @param string $folder Каталог.
		 * @param string|null $mask Маска шаблона.
		 * @param array $all_files Внутренний параметр для рекурсии.
		 * @return array Массив путей к файлам.
		 */
		public function listing(string $folder, string $mask = null, array &$all_files = []): array
		{
			$fp = @opendir($folder);
			if (!$fp) return $all_files;

			while (($cv_file = readdir($fp)) !== false)
			{
				if ($cv_file === '.' || $cv_file === '..') continue;
				$fullPath = $folder . DIRECTORY_SEPARATOR . $cv_file;

				if (is_file($fullPath))
				{
					if ($mask !== null)
					{
						if (fnmatch($mask, $cv_file)) $all_files[] = $fullPath;
					}
					else
					{
						$all_files[] = $fullPath;
					}
				} elseif (is_dir($fullPath))
				{
					$this->listing($fullPath, $mask, $all_files);
				}
			}
			closedir($fp);
			return $all_files;
		}


		/**
		 * Преобразование списка файлов в дерево.
		 * @param array $listing Массив путей файлов.
		 * @param string $delimiter Разделитель путей.
		 * @return array Структура дерева.
		 */
		public function listingToTree(array $listing = [], string $delimiter = DIRECTORY_SEPARATOR): array
		{
			$result = [];
			foreach ($listing as $_item)
			{
				$_array = array_reverse(explode($delimiter, $_item));
				$buffer = [];
				$prev_key = null;
				//Пройдемся по пути и выберем все элементы
				foreach ($_array as $_key => $_value)
				{
					// В начало вставляем путь, если это последний элемент (самый глубокий уровень)
					$buffer[$_value] = $buffer ? $buffer : $_item;
					//Удалим прошлую итерацию, т.к. если мы здесь - она еще не завершена
					if ($prev_key !== null) unset($buffer[$prev_key]);
					//Сохраним ключ на родительский элемент
					$prev_key = $_value;
				}
				//Объединима новую информацию с прежними сведениями
				$result = array_merge_recursive($result, $buffer);
			}
			return $result;
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


		/**
		 * Получение списка подкаталогов в директории.
		 * @param string $dir Путь к директории.
		 * @return array Массив имен подкаталогов.
		 */
		public function listingDir(string $dir): array
		{
			$result = [];
			$handle = @opendir($dir);  //Открываем директорию
			if (!$handle) return $result;

			//Если нам подсунули не пустую доректорию, то ставим слеш в конце
			if ($dir !== '' && substr($dir, -1) !== DIRECTORY_SEPARATOR) $dir .= DIRECTORY_SEPARATOR;

			while (($cat = readdir($handle)) !== false)
			{
				if ($cat === '.' || $cat === '..') continue;
				if (is_dir($dir . $cat))  $result[] = $cat;
			}
			closedir($handle);
			return $result;
		}


		/**
		 * Проверка существования директории и наличия внутри искомого имени.
		 * @param string $dir Путь к директории.
		 * @param string $name Имя папки.
		 * @return bool
		 */
		public function exists(string $dir, string $name): bool
		{
			$buf = $this->listingDir($dir);
			return in_array($name, $buf, true);
		}



		/**
		 * Получение группировки файлов по папкам.
		 * @param string $folder Путь к корневой папке.
		 * @param string|null $mask Маска шаблона.
		 * @return array Массив группированных файлов.
		 */
		public function collection(string $folder, string $mask = null): array
		{
			$all_files = $this->listing($folder);
			$result = [];

			foreach ($all_files as $fullfilename)
			{
				$info = pathinfo($fullfilename);
				if ($mask !== null && !fnmatch($mask, $info['basename'])) continue;

				$result[$info['dirname']][] = $info['basename'];
			}
			return $result;
		}

		/**
		 * Удаление файла или папки рекурсивно.
		 * @param string $path Путь к файлу или папке.
		 * @return bool Успешность операции.
		 */
		public function remove(string $path): bool
		{
			if (is_file($path)) return unlink($path);
			if (is_dir($path))
			{
				foreach(scandir($path) as $p)
					if (($p!='.') && ($p!='..'))
						$this->remove($path.DIRECTORY_SEPARATOR.$p);
				return rmdir($path);
			}
			return false;
		}

		/**
		 * Возвращает basename (написана из-за ошибки в работе стоковой функции basename с русскими буквами)
		 * @param string $path Полный путь.
		 * @return string
		 */
		function basename($path)
		{
			return substr(strrchr($path, DIRECTORY_SEPARATOR), 1);
		}



		/**
		 * Преобразование структуры $_FILES в более удобную.
		 * @param array $_files Необязательный параметр: Массив формата $_FILES. (Если не указать - возмет $_FILES)
		 * @return array Упрощенная структура файлов.
		 */
		public function uploadReformat(array $file_post=null): array
		{
			//Если нам забыли передать массив -
			if (empty($file_post)) $file_post = $_FILES;

			//Если конвертация не требуется - выходим
			if (!is_array($file_post['name'])) return [$file_post];

			$file_ary = [];
			$file_count = count($file_post['name']);
			$file_keys = array_keys($file_post);

			for ($i = 0; $i < $file_count; $i++)
				foreach ($file_keys as $key)
					$file_ary[$i][$key] = $file_post[$key][$i];

			return $file_ary;
		}



		/**
		 * Получение списка загруженных файлов.
		 * @param bool $tree Если true, структура сохраняется и вложена по полям.
		 * Если указан tree - прикрепит к каждому полю ввода список файлов. Если false - создаст "плосский" список с перечислением файлов
		 * @return array
		 */
		public function uploadList(bool $tree = true): array
		{
			$result = [];

			if ($tree)
			{
				foreach ($_FILES as $_key => $_filelist)
					$result[$_key] = $this->uploadReformat($_filelist);
			}
			else
			{
				foreach ($_FILES as $_key => $_filelist)
					$result = array_merge($result, $this->uploadReformat($_filelist));
			}

			return $result;
		}



		/**
		 * Загрузка и перемещение одного файла.
		 * @param array &$tmpFileRecord Записка о файле из $_FILES.
		 * @param string $targetDir Каталог назначения.
		 * @param string $prefix Префикс имени файла.
		 * @param string|null $filename Имя файла (если не указано, генерируется).
		 * @return array Обновленная информация о файле.
		 */
		public function uploadMoveSingleFile(array &$tmpFileRecord, string $targetDir, string $prefix = "content_", string $filename = null): array
		{
			//Получим расширение файла
			$ext = pathinfo($tmpFileRecord['name'], PATHINFO_EXTENSION);

			if (!is_dir($targetDir)) mkdir($targetDir, $this->umask, true);

			if (!$filename)
			{
				do {
					$new_filename = $targetDir . DIRECTORY_SEPARATOR . $prefix . uniqid() . "." . $ext;
				} while (file_exists($new_filename));
			} else
			{
				$new_filename = $targetDir . DIRECTORY_SEPARATOR . $filename;
			}

			//Переместим свежий файл в целевую директорию
			move_uploaded_file($tmpFileRecord['tmp_name'], $new_filename);
			//Установим дефолтные права
			chmod($new_filename, $this->umask);
			$tmpFileRecord['new_name'] = $new_filename;

			return $tmpFileRecord;
		}



		/**
		 * Перемещение всех загруженных файлов в целевую папку.
		 * @param string $targetDir Каталог назначения.
		 * @param bool $uniqueName Использовать уникальные имена.
		 * @param string $prefix Префикс для названий файлов.
		 * @return array Массив информации о файлах.
		 */
		public function uploadMove(string $targetDir, bool $uniqueName = true, string $prefix = "content_"): array
		{
			//Получим список загружаемых файлов
			$filesblocks = $this->uploadList();

			//Проверим существование директории и если ее нет - создадим
			if (! file_exists($targetDir)) mkdir($targetDir, $this->umask, true);


			foreach ($filesblocks as &$_filelist)
			{
				if (is_array($_filelist))
				{
					foreach ($_filelist as &$_file) {
						if (!is_array($_file) || $_file['error'] !== UPLOAD_ERR_OK) continue;
						$_file = $this->uploadMoveSingleFile($_file, $targetDir, $prefix, $uniqueName ? null : $_file['name']);
					}
				}
				elseif ($_filelist['error'] === UPLOAD_ERR_OK)
				{
					$fileName = $uniqueName ? null : $_filelist['name'];
					$this->uploadMoveSingleFile($_filelist, $targetDir, $prefix, $fileName);
				}
			}

			return $filesblocks;
		}




		/*
		 * Форматирует размер в удобочитаемый вид с использованием подходящих единиц измерения.
		 *
		 * @param int $size Исходный размер в байтах.
		 * @param array $units Массив единиц измерения (по умолчанию: байт, КБ, МБ, ГБ, ТБ).
		 * @return string Отформатированное строковое представление размера с выбранной единицей измерения.
		 */
		public function formatterSize(int $size, $units=['байт', 'КБ', 'МБ', 'ГБ', 'ТБ'])
		{
			$units = $units ?? ['байт', 'КБ', 'МБ', 'ГБ', 'ТБ'];
			$unitIndex = 0;

			while ($size >= 1024 && $unitIndex < count($units) - 1) {
				$size /= 1024;
				$unitIndex++;
			}

			return str_replace(".00", "", number_format($size, 2) . ' ' . $units[$unitIndex]);
		}



		/**
		 * Получает и возвращает информацию о файле.
		 *
		 * @param string $file Путь к файлу.
		 * @return array|null Массив с информацией о файле или null, если файл недоступен для чтения.
		 */
		public function info(string $file)
		{
			// Проверка, доступен ли файл для чтения. Если нет — возвращаем null.
			if (!is_readable($file)) return null;

			// Получение информации о пути файла: директория, расширение, имя и т.д.
			$pinfo = pathinfo($file);

			// Формируем массив с данными о файле
			$result['name']  = $this->basename($file); // Получение базового имени файла (метод, предположительно, возвращает имя без пути или расширения)
			$result['dir']   = $pinfo['dirname'];       // Директория файла
			$result['ext']   = $pinfo['extension'];     // Расширение файла
			$result['mime']  = mime_content_type($file); // MIME-тип файла
			$result['ctime'] = date('Y-m-d H:i:s', filectime($file)); // Время создания файла
			$result['atime'] = date('Y-m-d H:i:s', fileatime($file)); // Время последнего доступа
			$result['mtime'] = date('Y-m-d H:i:s', filemtime($file)); // Время последней модификации

			$result['bytes'] = filesize($file); // Получение размера файла в байтах
			$result['size']  = $this->formatterSize($result['bytes']); // Форматированный размер файла, в КБ, МБ и т.д.
			$result['type']  = ($ftype = explode('/', $result['mime']))[0] == 'application' ? $ftype[1] : $ftype[0]; // Определение типа файла на основе MIME-типа
			$result['link']  = $file; // Полный путь к файлу

			return $result;
		}


	}

	return new QFilesTools();
