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

		function __construct($files)
		{
			//Интерфейс файловой системы. Раньше это был QFilesystemTools (легаси),
			//теперь сюда подключается фасад $APP->files (QFilesTools с jail-проверками)
			$this->files = $files;
		}

		function runtime()
		{
			return round(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 5);
			//~ echo 'Время выполнения скрипта: '.round(microtime(true) - $start, 4).' сек.';
		}
	}




	# ---------------------------------------------------------------- #
	# --------------[ СОЗДАЕМ И ПОДКЛЮЧАЕМ ИНТЕРФЕЙС ]---------------- #
	# ---------------------------------------------------------------- #

	//Подключаем актуальный файловый интерфейс ($APP->files) вместо легаси QFilesystemTools
	return new Utils($this->files);

