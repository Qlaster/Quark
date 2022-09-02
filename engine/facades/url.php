<?php

	/*
	 * URL
	 * 
	 * Механика для работы с адресной строкой, страницами и ссылками
	 * Позволяет удобно оперировать базисом адресной строки
	 * 
	 * Version 1.0
	 * Copyright 2022 
	 * 		
	*/
	
	namespace unit\url;
		
	# ---------------------------------------------------------------- #
	#                  ОПИСАНИЕ     ИНТЕРФЕЙСА                         #
	# ---------------------------------------------------------------- #
	interface QURLInterface
	{	
		// Получить адрес хоста
		public function host();
		
		// Получить URL путь расположения от корня
		public function	home();
		
		// Запрашивает текущую страницу, к которй обратился пользователь
		public function page();
		
		// URI в первоначальнои виде (от корня, домен не возвращается), как он был запрошен клиентом
		public function uri();
		
		// Параметры скрипта
		public function params();
	}
	
	# ---------------------------------------------------------------- #
	#                 РЕАЛИЗАЦИЯ   ИНТЕРФЕЙСА                          #
	# ---------------------------------------------------------------- #
	class QURL implements QURLInterface
	{
		public $page = null;
	
		//Получить адрес домена (без http)
		public function host()
		{
			return $_SERVER['HTTP_HOST'];
		}

		public function home($verbatim=false)
		{
			$result = pathinfo($_SERVER['SCRIPT_NAME']);
			$result = $result['dirname'].'/';
			if ($verbatim == false) $result = $this->correct($result);
			return (string) $result;
		}

		public function page($verbatim=false)
		{
			if ($this->page === null)			
			{
				//Получаем URL домашней папки расположения
				$home = $this->Home($verbatim); 
				//Берем запрошенную страницу
				$uri  = parse_url($this->URI($verbatim)); 			
				$uri  = $uri['path'];
				
				//Вырезаем домашнюю папку из общего пути
				$this->page  = mb_substr($uri, mb_strlen($home), mb_strlen($uri)); 
			}
			
			if ($verbatim == false) return (string) $this->correct($this->page);
			return (string) $this->page;
		}

		//Получить весь ЧПУ с пареметрами url скрипта (домен не возвращается). ЧПУ возвращается полностью, исключая только домен.
		public function uri($verbatim=false)
		{
			$result = urldecode($_SERVER['REQUEST_URI']);			
			if ($verbatim == false) $result = $this->correct($result);
			return $result;
		}
		
		//Получить параметры скрипта. Если $array_type = true, то значения вернуть в виде ассоциативного масива. В противном случае - вернется строка параметров
		public function params($array_type=true)
		{
			if ($array_type == false) 
				return urldecode($_SERVER['QUERY_STRING']);
		}
	
		public function correct($url)
		{
			if ($url == "") return "";
			$url = trim ($url); //Удаляем пробелы с начала и конца URL
			while ( strpos($url,'//') !== false ) {$url = str_replace('//','/',$url);}	 //Заменяем двойные слеши одинарными
			//if (substr($url, -1) == '/') $url = substr($url, 0, -1); //Если в конце слеш - убираем
			//if (substr($url, 0, 1) == '/') $url = substr($url, 1, strlen($url)); //Если слеш в начале предложения - убираем
			//~ if ($url == '/') $url = ""; //Если после всех преобразований URL равен слешу - то по факту он пустой.
			return $url;
		}
		
	}


	# ---------------------------------------------------------------- #
	# --------------[ СОЗДАЕМ И ПОДКЛЮЧАЕМ ИНТЕРФЕЙС ]---------------- #
	# ---------------------------------------------------------------- #
	
	return new QURL;
