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

	namespace App\Facade;

	# ---------------------------------------------------------------- #
	#                  ОПИСАНИЕ     ИНТЕРФЕЙСА                         #
	# ---------------------------------------------------------------- #
	interface QRouteInterface
	{
		// Получить правила для uri запроса
		public function match($url);
	}


	# ---------------------------------------------------------------- #
	#                 РЕАЛИЗАЦИЯ   ИНТЕРФЕЙСА                          #
	# ---------------------------------------------------------------- #
	class Route implements QRouteInterface
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
				//JSON-список контроллеров в значении правила — разворачиваем
				if (is_string($value) and strpos(ltrim($value), '[') === 0)
					$value = json_decode($value, true) ?? $value;

				//В том случае, если в конфиге укзаны правила массивом
				(is_array($value)) ? $result = array_merge($result, $value) : $result[] = $value;
			}
			return $result;
		}

		//Хвост url после фиксированного префикса паттерна: admin/* + admin/test/x → test/x
		protected function tail($pattern, $url)
		{
			return substr($url, strcspn($pattern, '*?['));
		}

		//Монтирует url в запись-директорию: нормализует разделитель и доклеивает хвост
		protected function mount($record, $pattern, $url)
		{
			if ($record !== '' and substr($record, -1) !== '/' and substr($record, -1) !== '.')
				$record .= '/';
			return $record . $this->tail($pattern, $url);
		}

		public function match($url, array $sections=['hook', 'route'])
		{
			$result = [];
			foreach ($sections as $section)
				foreach ((array) ($this->config[$section] ?? []) as $pattern => $record)
					if (fnmatch($pattern, $url))
					{
						if (strlen($record) >= 1 && $record[0]=='=')
						{
							if ($record[1]=='>')
							{
								$result[] = $this->mount(ltrim($record, '=> '), $pattern, $url);
								return $this->reassembly($result);
							}

							$result[] = ltrim($record, '= ');
							return $this->reassembly($result);
						}
						$result[] = $record[0]=='>' ? $this->mount(ltrim($record, '> '), $pattern, $url) : $record;
					}
			return $this->reassembly($result);
		}
	}


	# ---------------------------------------------------------------- #
	# --------------[ СОЗДАЕМ И ПОДКЛЮЧАЕМ ИНТЕРФЕЙС ]---------------- #
	# ---------------------------------------------------------------- #

	return new Route( $this->config->get(__file__) );


