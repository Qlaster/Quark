<?php 

	/*
	 * URL
	 * 
	 * Механика для работы с адресной строкой, страницами и ссылками
	 * Поддерживает редирект, псевдонимы, маршруты и маски роутинга
	 * оригинальная идея 		http://usman.it/php-router-140-characters/
	 * 
	 * Version 1.0
	 * Copyright 2022 
	 * 		
	*/
	
	namespace unit\route;
	
	# ---------------------------------------------------------------- #
	#                  ОПИСАНИЕ     ИНТЕРФЕЙСА                         #
	# ---------------------------------------------------------------- #
	interface QRoutInterface
	{
		// Получить страницу
		public function rule($url);
		
		// Установить (сохранить) страницу
		public function hook($url);
		
		// Показ всех страниц
		public function alias($url);
	}
	

	# ---------------------------------------------------------------- #
	#                 РЕАЛИЗАЦИЯ   ИНТЕРФЕЙСА                          #
	# ---------------------------------------------------------------- #
	class Route implements QRoutInterface
	{
		
		public 		$config = array();
		protected 	$hooks	= array();

		function __construct($config=[])
		{
			$this->config = $config;
			//Устанавливаем пустой конфиг
			if (! isset($this->config['route']))	$this->config['route']    = [];
			if (! isset($this->config['filter'])) 	$this->config['filter']	  = [];
			if (! isset($this->config['redirect']))	$this->config['redirect'] = [];
			
		}
		
		

		//Проверяет, есть ли правило для указанного uri. Если да - то возвращает его, иначе - false
		public function rule($url)
		{		
			foreach ($this->config['route'] as $pattern => $controller) 
			{
				if ( fnmatch($pattern, $url) ) return $controller;
			}			
			return false;
		}


		//Проверяет, есть ли правило для указанного uri. Если да - то возвращает его, иначе - false
		public function hook($url)
		{		
			foreach ($this->config['hook'] as $pattern => $controller) 
			{
				if ( fnmatch($pattern, $url) ) return $controller;
			}			
			return false;
		}

		public function alias($url)
		{			
			foreach ($this->config['alias'] as $pattern => $alias) 
			{
				if ( fnmatch($pattern, $url) ) return $alias;
			}				
			return false;
		}

	}


	# ---------------------------------------------------------------- #
	# --------------[ СОЗДАЕМ И ПОДКЛЮЧАЕМ ИНТЕРФЕЙС ]---------------- #
	# ---------------------------------------------------------------- #
		
	return new Route( $this->config->get(__file__) );
	
	
