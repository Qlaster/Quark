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
		// Получить правила для uri запроса
		public function match($url);
	}


	# ---------------------------------------------------------------- #
	#                 РЕАЛИЗАЦИЯ   ИНТЕРФЕЙСА                          #
	# ---------------------------------------------------------------- #
	class Route implements QRoutInterface
	{

		public $config = array();

		function __construct($config=[])
		{
			$this->config = $config;
		}

		/**
		* Механизм пересборки параметров правил роутинга
		*
		* @rules array
		* return array
		*/
		protected function reassembly($rules)
		{
			$result = [];
			foreach ($rules as $value)
			{
				//В том случае, если значение указано в формате json
				if ($json = json_decode($value, true)) $value = $json;
				//В том случае, если в конфиге укзаны правила массивом
				(is_array($value)) ? $result += $value : $result[] = $value;
			}
			return $result;
		}

		public function match($url, array $sections=['hook', 'route'])
		{
			$result = [];
			foreach ($sections as $section)
				foreach ((array) $this->config[$section] as $pattern => $record)
					if (fnmatch($pattern, $url))
					{
						if ($record[0]=='=')
						{
							if ($record[1]=='>')
							{
								$result[] = ltrim($record, '=> ').$url;
								return $this->reassembly($result);
							}

							$result[] = ltrim($record, '= ');
							return $this->reassembly($result);
						}
						$result[] = $record[0]=='>' ? ltrim($record, '> ').$url : $record;
					}
			return $this->reassembly($result);
		}
	}


	# ---------------------------------------------------------------- #
	# --------------[ СОЗДАЕМ И ПОДКЛЮЧАЕМ ИНТЕРФЕЙС ]---------------- #
	# ---------------------------------------------------------------- #

	return new Route( $this->config->get(__file__) );


