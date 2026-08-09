<?php

	/*
	 * config
	 *
	 * Version 2
	 * Copyright 2026
	 *
	 * - Получить конфигурацию модуля
	 * $APP->config->get();
	 *
	 * - Сохранить конфигурацию модуля
	 * $APP->config->set($config);
	 *
	 * Конвенция: ini-файл лежит рядом с php-файлом вызывающего кода.
	 * Если имя файла не передано, оно выводится через debug_backtrace.
	 *
	 * Экранирование в ключах: ".." — литеральная точка внутри сегмента,
	 * одиночная "." — разделитель вложенности.
	 *
	*/

	namespace App\Facade;

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
		private $reader;
		private $writer;
		private $cache = [];	//распарсенные конфиги в пределах запроса (путь => config)

		private function reader()
		{
			return $this->reader ?: $this->reader = new Core_Ini_Reader;
		}

		private function writer()
		{
			return $this->writer ?: $this->writer = new Core_Ini_Writer;
		}

		public function get($filename=null)
		{
			//Если имя файла не передано — берём файл вызывающего кода
			if ($filename == null)
				$filename = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0]['file'];

			$path = $this->compIniFile($filename);

			//Кэшируем и отрицательный результат — отсутствующий файл не дёргаем stat'ом повторно
			if (array_key_exists($path, $this->cache)) return $this->cache[$path];
			if (!file_exists($path))                 return $this->cache[$path] = null;

			return $this->cache[$path] = $this->reader()->fromFile($path);
		}

		public function set($config, $filename=null)
		{
			//Если имя файла не передано — берём файл вызывающего кода
			if ($filename == null)
				$filename = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0]['file'];

			$path   = $this->compIniFile($filename);
			$result = file_put_contents($path, $this->writer()->processConfig($config), LOCK_EX);

			//Сбрасываем кэш — следующее чтение вернёт каноническую (распарсенную) форму
			unset($this->cache[$path]);

			return $result;
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
			if (!is_readable($filename)) return null;

			if (!isset($this->cache[$filename]))
				$this->cache[$filename] = $this->reader()->fromFile($filename);

			return $this->cache[$filename];
		}

		/*
		 *
		 * name: Load ENV file to $_ENV
		 * @param  path to env file
		 * @return (bool) result operation?
		 *
		 */
		public function loadENV($files, $options=[])
		{
			//Default options
			if (!isset($options['replace'])) $options['replace'] = false;

			foreach ((array) $files as $envfile)
			{
				if ($ENV = (array) $this->readFile($envfile))
				{
					//Дополняем переменные окружения
					$_ENV = $options['replace'] ? array_replace($_ENV, $ENV) : array_replace($ENV, $_ENV);
				}
			}
		}

		public function fromString(string $string)
		{
			return $this->reader()->fromString($string);
		}

		public function toString($config)
		{
			return $this->writer()->processConfig((array) $config);
		}


		//Ты ей файл модуля, она тебе путь к файлу конфигурации, который лежит в той же папке
		private function compIniFile($filename, $ext='ini')
		{
			//Ищем конфигурацию модуля в той же папке, где он лежит, но только с расширением ini
			$PI = pathinfo($filename);
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
		 * Маркер для литеральной точки (".."") — управляющий символ,
		 * который не может встретиться в реальном ini-файле.
		 *
		 * @var string
		 */
		const LITERAL_DOT = "\x1A";

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
				throw new \Exception (sprintf(
					"File '%s' doesn't exist or not readable",
					$filename
				));
			}

			$this->directory = dirname($filename);

			set_error_handler(
				function ($error, $message = '', $file = '', $line = 0) use ($filename) {
					throw new \Exception (
						sprintf('Error reading INI file "%s": %s', $filename, $message),
						$error
					);
				},
				E_WARNING
			);

			try
			{
				$ini = $this->parseIniFile($filename);
			}
			finally
			{
				restore_error_handler();
			}

			return $this->process($ini);
		}


		public function fromString($iniString)
		{
			//Строковый конфиг не имеет директории — @include в нём недоступен
			$this->directory = null;
			return $this->process($this->parseIniString($iniString));
		}


		/**
		 * Разбивает ключ или имя секции на сегменты с учётом эскейпинга:
		 * ".."" — литеральная точка, "." — разделитель вложенности.
		 *
		 * @param  string $key
		 * @return array
		 */
		private function splitKey($key)
		{
			//Быстрый путь: без ".." маскировка не нужна
			if (strpos($key, '..') === false)
				return explode($this->nestSeparator, $key);

			$segments = explode($this->nestSeparator, str_replace('..', self::LITERAL_DOT, $key));

			foreach ($segments as &$_segment)
				$_segment = str_replace(self::LITERAL_DOT, '.', $_segment);

			return $segments;
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
					$segments = $this->splitKey($section);

					if (count($segments) > 1) //нет — уже готово
					//placeholder
					{
						$config = array_merge_recursive($config, $this->buildNestedSection($segments, $value));
					} else
					{
						$config[$segments[0]] = $this->processSection($value);
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

			foreach ($section as $key => $value)
				$this->processKey($key, $value, $config);

			return $config;
		}

		/**
		 * Process a key.
		 *
		 * @param  string $key
		 * @param  string $value
		 * @param  array  $config
		 * @return array
		 * @throws Exception
		 */
		protected function processKey($key, $value, array &$config)
		{
			//Быстрый путь: плоский ключ — без splitKey и без цикла
			if (strpos($key, $this->nestSeparator) === false)
			{
				if ($key === '@include') $this->processInclude($value, $config);
				else                     $config[$key] = $value;
				return;
			}

			$segments = $this->splitKey($key);
			$ref      = &$config;

			//Разворачиваем путь вложенности, попутно создавая промежуточные уровни
			while (count($segments) > 1)
			{
				$head = array_shift($segments);

				if (!isset($ref[$head]))
				{
					$ref[$head] = array();
				}
				elseif (!is_array($ref[$head]))
				{
					throw new \Exception (
						sprintf('Cannot create sub-key for "%s", as key already exists', $head)
					);
				}

				$ref = &$ref[$head];
			}

			$leaf = $segments[0];

			//@include подгружает другой ini-файл относительно директории текущего
			if ($leaf === '@include')
			{
				$this->processInclude($value, $ref);
				return;
			}

			$ref[$leaf] = $value;
		}

		/**
		 * Обработка директивы @include: подгружает ini-файл и сливает его в текущий уровень.
		 *
		 * @param  string $value
		 * @param  array  $ref
		 */
		private function processInclude($value, array &$ref)
		{
			if ($this->directory === null)
				throw new \Exception ('Cannot process @include statement for a string config');

			$reader = clone $this;
			$ref    = array_replace_recursive($ref, $reader->fromFile($this->directory . '/' . $value));
		}


		function parseIniString($strings, $sections=true)
		{
			//Строковый вход: разделяем по любому стилю окончаний строк (CRLF / CR / LF)
			if (is_string($strings))
				$strings = explode("\n", str_replace(array("\r\n", "\r"), "\n", $strings));

			$result  = array();
			$section = null;

			foreach ($strings as $string)
			{
				$string = trim($string);

				//Пустые строки и полнолинейные комментарии
				if ($string == '' or $string[0] == '#' or $string[0] == ';') continue;

				//Секция
				if ($string[0] == '[' and mb_substr($string, -1) == ']')
				{
					$section = mb_substr($string, 1, -1);
					$result[$section] = array();
					continue;
				}

				//Разбираем строку на ключ и значение по первому "="
				$separator = mb_strpos($string, '=');
				if ($separator === false) continue;

				$key   = trim(mb_substr($string, 0, $separator));
				$value = trim(mb_substr($string, $separator + 1));

				//Снимаем обрамляющие кавычки со значения (если они есть)
				if (mb_strlen($value) >= 2)
				{
					$quote = $value[0];
					if (($quote == '"' or $quote == "'") and mb_substr($value, -1) == $quote)
						$value = mb_substr($value, 1, -1);
				}

				if ($section) $result[$section][$key] = $value;
				else          $result[$key]           = $value;
			}

			return $result;
		}

		function parseIniFile($filename, $sections=true)
		{
			if (! file_exists($filename) ) throw new \Exception ('File INI not found');

			return $this->parseIniString(file($filename, FILE_IGNORE_NEW_LINES), $sections);
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
						$iniString .= $this->escapeKey($sectionName)
								   .  ' = '
								   .  $this->prepareValue($data)
								   .  "\n";
					} else {
						$iniString .= '[' . $this->escapeKey($sectionName) . ']' . "\n"
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
					//Сегменты с литеральной точкой удваиваем — разделитель экранируется самим собой
					$iniString .= implode($this->nestSeparator, array_map(array($this, 'escapeKey'), $group))
							   .  ' = '
							   .  $this->prepareValue($value)
							   .  "\n";
				}
			}

			return $iniString;
		}

		/**
		 * Экранирует сегмент ключа: литеральная точка удваивается,
		 * одиночная точка остаётся разделителем вложенности.
		 *
		 * @param  string $key
		 * @return string
		 */
		protected function escapeKey($key)
		{
			//Перенос строки в ключе ломает построчный формат ini — запрещаем
			if (strpbrk($key, "\r\n") !== false)
				throw new \Exception (sprintf('INI key can not contain line breaks: "%s"', $key));

			return str_replace('.', '..', $key);
		}

		/**
		 * Prepare a value for INI.
		 *
		 * @param  mixed $value
		 * @return string
		 */
		protected function prepareValue($value)
		{
			//Перенос строки в значении ломает построчный формат ini — запрещаем
			if (strpbrk((string) $value, "\r\n") !== false)
				throw new \Exception ('INI value can not contain line breaks');

			if (is_int($value) || is_float($value)) {
				return $value;
			} elseif (is_bool($value)) {
				return ($value ? 'true' : 'false');
			} elseif (false === strpos((string)$value, '"')) {
				return '"' . $value .  '"';
			}

			//Значения с двойными кавычками внутри (например JSON) пишем сыром —
			//читатель снимает только обрамляющие кавычки, строка останется валидной
			return $value;
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
