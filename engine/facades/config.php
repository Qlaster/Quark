<?php

	/*
	 * config
	 *
	 * Version 1.0
	 * Copyright 2022
	 *
	 *
	 * - Получить конфигурацию модуля
	 * $APP->config->get();
	 *
	 * - Сохранить конфигурацию модуля
	 * $APP->config->get($config);
	 *
	 *
	*/

	//~ namespace unit\config;

	# ---------------------------------------------------------------- #
	#                  ОПИСАНИЕ     ИНТЕРФЕЙСА                         #
	# ---------------------------------------------------------------- #
	interface QConfigInterface
	{
		// Получить конфигурацию модуля
		public function get();

		// Перезаписать конфигурацию модуля
		public function set($config);
	}

	# ---------------------------------------------------------------- #
	#                 РЕАЛИЗАЦИЯ   ИНТЕРФЕЙСА                          #
	# ---------------------------------------------------------------- #
	class Config implements QConfigInterface
	{
		public function get($filename=null)
		{
			static $ini;
			if (!isset($ini)) $ini = new Core_Ini_Reader;

			//Если нам забыли указать имя файла, вызываем отладчик и посмотрим сами
			if ($filename == null)
			{
				//Это пипец как дорого, но вариантов не много.
				$debug 		= debug_backtrace();
				$filename 	= $debug[0]['file'];
			}

			//Конфигурируем ссылку на ini файл
			$PI = $this->CompIniFile($filename);
			// Обрабатываем конфиг если файл существует
			if (file_exists($PI))
				return $ini->fromFile($PI);

			return null;
		}

		public function set($config, $filename=null)
		{
			static $ini;
			if (!isset($ini)) $ini = new Core_Ini_Writer;

			//Если нам забыли указать имя файла, вызываем отладчик и посмотрим сами
			if ($filename == null)
			{
				$debug 		= debug_backtrace();
				$filename 	= $debug[0]['file'];
			}

			//Конфигурируем ссылку на ini файл
			$PI = $this->CompIniFile($filename);

			$inistring = $ini->processConfig($config);
			return file_put_contents($PI, $inistring);
		}


		/*
		 *
		 * name: readFile
		 * @param (string) path to config file
		 * @return (array) configuration
		 *
		 */
		public function readFile($filename)
		{
			static $ini;
			if (!isset($ini)) $ini = new Core_Ini_Reader;

			if (is_readable($filename))
				return $ini->fromFile($filename);
			return null;
		}

		/*
		 *
		 * name: Load ENV file to $_ENV
		 * @param  path to env file
		 * @return (bool) result operation?
		 *
		 */
		public function loadENV($files, $options=null)
		{
			foreach ((array) $files as $envfile)
			{
				if ($ENV = $this->readFile($envfile))
				{
					//Дополняем переменные окружения
					$_ENV = array_replace($_ENV, (array) $ENV);
				}
			}
		}

		public function fromString(string $string)
		{
			static $ini;
			if (!isset($ini)) $ini = new Core_Ini_Reader;

			return $ini->fromString($string);
		}

		public function toString($config)
		{
			static $ini;
			if (!isset($ini)) $ini = new Core_Ini_Writer;

			return $ini->processConfig($config);
		}


		//Ты ей файл модуля, она тебе путь к файлу конфигурации, который лежит в той е папке
		private function CompIniFile($filename, $ext='ini')
		{
			//Еще нужно сделать проверку, по которой, будет контролироваться путь к файлу, что бы он не уходил дальше public директории фо\реймворка
			$PI = pathinfo($filename);
			//Суть кода в следующем. Мы ищем конфигурацию модуля в той же папке, где он лежит, но только с расширением ini
			return $PI['dirname'].DIRECTORY_SEPARATOR.$PI['filename'].".$ext";
		}


	}




	# ---------------------------------------------------------------- #
	#               ВСПОМОГАТЕЛЬНЫЕ    СТРУКТУРЫ                       #
	# ---------------------------------------------------------------- #


	/**
	 * INI config reader.
	 */
	class Core_Ini_Reader
	{
		/**
		 * Separator for nesting levels of configuration data identifiers.
		 *
		 * @var string
		 */
		protected $nestSeparator = '.';

		/**
		 * Directory of the file to process.
		 *
		 * @var string
		 */
		protected $directory;

		/**
		 * fromFile(): defined by Reader interface.
		 *
		 * @see    ReaderInterface::fromFile()
		 * @param  string $filename
		 * @return array
		 * @throws Exception
		 */
		public function fromFile($filename)
		{
			if (!is_file($filename) || !is_readable($filename))
			{
				throw new Exception (sprintf(
					"File '%s' doesn't exist or not readable",
					$filename
				));
			}

			$this->directory = dirname($filename);

			set_error_handler(
				function ($error, $message = '', $file = '', $line = 0) use ($filename) {
					throw new Exception (
						sprintf('Error reading INI file "%s": %s', $filename, $message),
						$error
					);
				},
				E_WARNING
			);
			$ini = $this->my_parse_ini_file($filename, true);
			restore_error_handler();

			return $this->process($ini);
		}

		/**
		 * fromString(): defined by Reader interface.
		 *
		 * @param  string $string
		 * @return array|bool
		 * @throws Exception
		 */
		public function fromString($string)
		{
			if (empty($string)) {
				return array();
			}
			$this->directory = null;

			set_error_handler(
				function ($error, $message = '', $file = '', $line = 0) {
					throw new Exception (
						sprintf('Error reading INI string: %s', $message),
						$error
					);
				},
				E_WARNING
			);
			$ini = parse_ini_string($string, true);
			restore_error_handler();

			return $this->process($ini);
		}

		/**
		 * Process data from the parsed ini file.
		 *
		 * @param  array $data
		 * @return array
		 */
		protected function process(array $data)
		{
			$config = array();

			foreach ($data as $section => $value)
			{
				if (is_array($value))
				{
					if (mb_strpos($section, $this->nestSeparator) !== false)
					{
						$sections = explode($this->nestSeparator, $section);
						$config = array_merge_recursive($config, $this->buildNestedSection($sections, $value));
					} else
					{
						$config[$section] = $this->processSection($value);
					}
				} else
				{
					$this->processKey($section, $value, $config);
				}
			}

			return $config;
		}

		/**
		 * Process a nested section
		 *
		 * @param array $sections
		 * @param mixed $value
		 * @return array
		 */
		private function buildNestedSection($sections, $value)
		{
			if (count($sections) == 0) {
				return $this->processSection($value);
			}

			$nestedSection = array();

			$first = array_shift($sections);
			$nestedSection[$first] = $this->buildNestedSection($sections, $value);

			return $nestedSection;
		}

		/**
		 * Process a section.
		 *
		 * @param  array $section
		 * @return array
		 */
		protected function processSection(array $section)
		{
			$config = array();

			foreach ($section as $key => $value) {
				$this->processKey($key, $value, $config);
			}

			return $config;
		}

		/**
		 * Process a key.
		 *
		 * @param  string $key
		 * @param  string $value
		 * @param  array  $config
		 * @return array
		 * @throws Exception\RuntimeException
		 */
		protected function processKey($key, $value, array &$config)
		{
			if (mb_strpos($key, $this->nestSeparator) !== false) {
				$pieces = explode($this->nestSeparator, $key, 2);

				if (!mb_strlen($pieces[0]) || !mb_strlen($pieces[1])) {
					throw new Exception (sprintf('Invalid key "%s"', $key));
				} elseif (!isset($config[$pieces[0]])) {
					if ($pieces[0] === '0' && !empty($config)) {
						$config = array($pieces[0] => $config);
					} else {
						$config[$pieces[0]] = array();
					}
				} elseif (!is_array($config[$pieces[0]])) {
					throw new Exception (
						sprintf('Cannot create sub-key for "%s", as key already exists', $pieces[0])
					);
				}

				$this->processKey($pieces[1], $value, $config[$pieces[0]]);
			} else {
				if ($key === '@include') {
					if ($this->directory === null) {
						throw new Exception ('Cannot process @include statement for a string config');
					}

					$reader  = clone $this;
					$include = $reader->fromFile($this->directory . '/' . $value);
					$config  = array_replace_recursive($config, $include);
				} else {
					$config[$key] = $value;
				}
			}
		}



		//аналог pars_ini_file
		function  my_parse_ini_file($filename, $sections=true)
		{
			if (! file_exists($filename) ) throw new Exception ('File INI not found');
			$ini = file($filename);

			$result = array();

			foreach ($ini as $string)
			{
				$string = trim($string);
				if (($string == '') or ($string[0] == '#') or ($string[0] == ';')) continue;


				//Это секция
				if (($string[0] == '[') and (mb_substr($string, -1) == ']'))
				{
					$section_name = mb_substr($string, 1, -1);
					$result[$section_name] = array();
				}

				$separator = mb_strpos($string, '=');
				//=== не стоит потому, что даже теоретически ini строка не может начинаться со знака равно
				//TODO: исправлено - в некоторых конфигах есть такая потребность, значит все таки может=)
				if ($separator !== false)
				{
					//Разберем строку на ключ и значеник
					$value 	= trim( mb_substr($string, $separator+1) );
					if ($separator == 0) $separator = 1;
					$var 	= trim( mb_substr($string, 0, $separator-1) );

					//Очистим озачение от ковычек (если ни имеются, конечно)
					if ($value != '')
						if ( ((mb_substr($value, 0, 1) == '"') and (mb_substr($value, -1) == '"')) or ((mb_substr($value, 0, 1) == "'") and (mb_substr($value, -1) == "'"))  )
						{
							$value = mb_substr($value, 1, -1);
						}

					//Если мы находимся внутри секции - то будем добавлять перемнные туда. А вот если нет - то просто кинем их в корень
					if (isset($section_name) and ($section_name != ''))
					{
						$result[$section_name][$var] = $value;
					}
					else
					{
						$result[$var] = $value;
					}

				}


			}

			return $result;
		}



	}


	/**
	 * INI config Writer.
	 */
	class Core_Ini_Writer
	{
		/**
		 * Separator for nesting levels of configuration data identifiers.
		 *
		 * @var string
		 */
		protected $nestSeparator = '.';

		/**
		 * If true the INI string is rendered in the global namespace without
		 * sections.
		 *
		 * @var bool
		 */
		protected $renderWithoutSections = false;

		/**
		 * Set if rendering should occur without sections or not.
		 *
		 * If set to true, the INI file is rendered without sections completely
		 * into the global namespace of the INI file.
		 *
		 * @param  bool $withoutSections
		 * @return Ini
		 */
		public function setRenderWithoutSectionsFlags($withoutSections)
		{
			$this->renderWithoutSections = (bool) $withoutSections;
			return $this;
		}

		/**
		 * Return whether the writer should render without sections.
		 *
		 * @return bool
		 */
		public function shouldRenderWithoutSections()
		{
			return $this->renderWithoutSections;
		}

		/**
		 * processConfig(): defined by AbstractWriter.
		 *
		 * @param  array $config
		 * @return string
		 */
		public function processConfig(array $config)
		{
			$iniString = '';

			if ($this->shouldRenderWithoutSections()) {
				$iniString .= $this->addBranch($config);
			} else {
				$config = $this->sortRootElements($config);

				foreach ($config as $sectionName => $data) {
					if (!is_array($data)) {
						$iniString .= $sectionName
								   .  ' = '
								   .  $this->prepareValue($data)
								   .  "\n";
					} else {
						$iniString .= '[' . $sectionName . ']' . "\n"
								   .  $this->addBranch($data)
								   .  "\n";
					}
				}
			}

			return $iniString;
		}

		/**
		 * Add a branch to an INI string recursively.
		 *
		 * @param  array $config
		 * @param  array $parents
		 * @return string
		 */
		protected function addBranch(array $config, $parents = array())
		{
			$iniString = '';

			foreach ($config as $key => $value) {
				$group = array_merge($parents, array($key));

				if (is_array($value)) {
					$iniString .= $this->addBranch($value, $group);
				} else {
					$iniString .= implode($this->nestSeparator, $group)
							   .  ' = '
							   .  $this->prepareValue($value)
							   .  "\n";
				}
			}

			return $iniString;
		}

		/**
		 * Prepare a value for INI.
		 *
		 * @param  mixed $value
		 * @return string
		 * @throws Exception
		 */
		protected function prepareValue($value)
		{
			if (is_int($value) || is_float($value)) {
				return $value;
			} elseif (is_bool($value)) {
				return ($value ? 'true' : 'false');
			} elseif (false === mb_strpos($value, '"')) {
				return '"' . $value .  '"';
			} else {
				return $value;
				throw new Exception ('Value can not contain double quotes');
			}
		}

		/**
		 * Root elements that are not assigned to any section needs to be on the
		 * top of config.
		 *
		 * @param  array $config
		 * @return array
		 */
		protected function sortRootElements(array $config)
		{
			$sections = array();

			// Remove sections from config array.
			foreach ($config as $key => $value) {
				if (is_array($value)) {
					$sections[$key] = $value;
					unset($config[$key]);
				}
			}

			// Read sections to the end.
			foreach ($sections as $key => $value) {
				$config[$key] = $value;
			}

			return $config;
		}




	}







	# ---------------------------------------------------------------- #
	# --------------[ СОЗДАЕМ И ПОДКЛЮЧАЕМ ИНТЕРФЕЙС ]---------------- #
	# ---------------------------------------------------------------- #

	return new Config;
