<?php

	/*
	 * l10n — локализация данных по маркерам
	 *
	 * Version 1.0
	 * Copyright 2026
	 *
	 * Суть: узел данных помечается ключом 'l10n' = 'zone:type:name'.
	 * Фасад обходит структуру, находит маркеры и перезаписывает поля узла
	 * значениями из одноимённой секции локали.
	 *
	 * Источники локали: app/l10n/{lang}.ini + плоские {lang}/*.ini —
	 * загружаются целиком при первом обращении. Вложенные файлы
	 * {lang}/{zone}/{type}/... грузятся лениво по пути секции:
	 * для 'admin:comm:talk' пробуются {lang}/admin/comm.ini и
	 * {lang}/admin/comm/talk.ini, найденное сливается от мелкого
	 * к глубокому — глубокий перекрывает. Промах → оригинал.
	 *
	 *	//данные
	 *	$item = ['head'=>'Мониторинг', 'icon'=>'fa fa-th-large', 'l10n'=>'admin:menu:dashboard'];
	 *
	 *	//app/l10n/en.ini
	 *	[admin:menu:dashboard]
	 *	head = "Monitoring"
	 *	icon = "fa fa-dashboard"
	 *
	 * Переводится любое поле узла — надписи, иконки, классы, ссылки.
	 * Нет маркера / нет секции / нет поля — оригинал остаётся как есть.
	 *

		//Перевести структуру данных (маркеры l10n) на текущий язык
		$content = $APP->l10n->translate($content);

		//...или на указанный язык явно (состояние не меняет)
		$content = $APP->l10n->translate($content, 'en');

		//Скаляр: секция [t] локали, ключом служит сам текст-оригинал.
		//Промах → оригинал (единый контракт «нет совпадения = оригинал»)
		$content['title'] = $APP->l10n->translate('Журнал');	//[t] "Журнал" = "Journal"

		//Ссылка на ключ: вернуть целую секцию или одно её поле.
		//Lookup-режим: промах → null (удобно с оператором ??)
		$APP->l10n->translate(['admin:tools:journal']);           //секция целиком
		$APP->l10n->translate(['admin:tools:journal', 'head']);   //одно поле

		//Машинный перевод: подменить значения перечисленных полей
		//словарём секции [auto] локали (точное → нормализованное)
		$content = $APP->l10n->auto($content, ['head','text']);

		//Текущий целевой язык / установить (запоминается в сессии)
		$APP->l10n->lang();
		$APP->l10n->lang('en');

		//Промахи, собранные при [collect] missing = 1 (для редактора переводов).
		//Только память: персистентность — забота вызывающего контроллера
		$APP->l10n->missing();			//вернуть
		$APP->l10n->missing(true);		//вернуть и очистить

	*/

	namespace App\Facade;

	# ---------------------------------------------------------------- #
	#                  ОПИСАНИЕ     ИНТЕРФЕЙСА                         #
	# ---------------------------------------------------------------- #
	interface QL10nInterface
	{
		//Перевести данные на язык $lang (null — текущий).
		//Массив — обход маркеров l10n; строка — словарь [t] по тексту;
		//список ['key'] / ['key','field'] — разрешение секции/поля (промах → null)
		public function translate($data, $lang = null);

		//Машинный перевод: поля из $fields ищутся в словаре [auto] локали
		public function auto($data, $fields = null, $lang = null);

		//Текущий целевой язык или его установка (возвращает $this)
		public function lang($set = null);

		//Собранные промахи (в памяти); $flush — очистить после чтения
		public function missing($flush = false);
	}


	# ---------------------------------------------------------------- #
	#                 РЕАЛИЗАЦИЯ   ИНТЕРФЕЙСА                          #
	# ---------------------------------------------------------------- #
	class QL10n implements QL10nInterface
	{
		//Распарсенный l10n.ini — только вход, фасад его не мутирует:
		//состояние (язык) живёт в сессии/памяти, не в конфиге
		public $config;

		//Кеш загруженных локалей: {lang} => распарсенный ini-массив | null
		private $locale = [];

		//Кеш проб ленивой загрузки: уже опрошенные файлы по пути секции
		private $probed = [];

		//Нормализованный индекс словарных секций ([t],[auto]) по языку
		private $dictIndex = [];

		//Промахи за запрос: 'lang|ключ' => ['key'=>..., 'orig'=>...]
		private $missing = [];

		//Язык, выставленный lang() без сессии (CLI/тесты/до session_start)
		private $langSet = null;

		public function __construct($config = [])
		{
			$this->config = (array) $config;
		}


		# -------------------------[ ПУБЛИЧНОЕ ]------------------------- #

		//Текущий целевой язык: сессия → конфиг lang → source.
		//Со строковым аргументом — устанавливает язык (сессия), вернёт $this
		public function lang($set = null)
		{
			if ($set !== null)
			{
				//сессия основной канал; без неё (CLI/тесты) — память
				if (isset($_SESSION)) $_SESSION['l10n']['lang'] = $set;
				else                 $this->langSet = $set;
				return $this;
			}
			return $_SESSION['l10n']['lang']
			    ?? $this->langSet
			    ?? $this->config['translate']['lang']
			    ?? $this->config['translate']['source']
			    ?? null;
		}

		//Полиморфен по входу:
		// - список ['key']|['key','field'] → разрешение секции/поля (промах → null)
		// - строка  → словарь [t] по самому тексту (промах → оригинал)
		// - массив  → рекурсивный обход маркеров l10n
		public function translate($data, $lang = null)
		{
			if (!$this->needed($lang = $lang ?: $this->lang())) return $data;

			if (is_string($data))
				return $this->dict($lang, 't', $data);

			if (!is_array($data)) return $data;

			//list-форма разрешается только на верхнем уровне —
			//внутри рекурсии списки строк ключами не считаются
			if ($this->isRef($data))
				return $this->resolveRef($lang, $data[0], $data[1] ?? null);

			return $this->walk($data, $lang);
		}

		//Gettext-режим: у каждого узла поля из $fields (или autofields
		//конфига) заменяются переводом значения из словаря [auto] локали
		public function auto($data, $fields = null, $lang = null)
		{
			if (!$this->needed($lang = $lang ?: $this->lang())) return $data;

			if ($fields === null)
				$fields = $this->config['auto']['autofields'] ?? ['head', 'text'];
			if (is_string($fields))
				$fields = preg_split('/\s*,\s*/', trim($fields), -1, PREG_SPLIT_NO_EMPTY);

			return $this->autoWalk($data, $lang, (array) $fields);
		}

		//Промахи за запрос (в памяти — куда их складывать решает контроллер)
		public function missing($flush = false)
		{
			$out = $this->missing;
			if ($flush) $this->missing = [];
			return $out;
		}


		# -------------------------[ ВНУТРЕННЕЕ ]------------------------ #

		//Внутренний gate: локализация имеет смысл, только если включена,
		//язык отличается от языка оригинала и файл локали существует
		private function needed($lang)
		{
			if (empty($this->config['translate']['enable'])) return false;
			if (!$lang || $lang === ($this->config['translate']['source'] ?? null)) return false;
			return $this->load($lang) !== null;
		}

		//Загрузить локаль в кеш: {folder}/{lang}.ini + merge {folder}/{lang}/*.ini
		private function load($lang)
		{
			if (array_key_exists($lang, $this->locale)) return $this->locale[$lang];

			$dir   = rtrim($this->config['translate']['folder'] ?? 'app/l10n', '/\\');
			$files = [];
			if (is_file("$dir/$lang.ini")) $files[] = "$dir/$lang.ini";
			foreach ((glob("$dir/$lang/*.ini") ?: []) as $f) $files[] = $f;
			if (!$files) return $this->locale[$lang] = null;

			//Core_Ini_Reader — та же семантика что у компаньон-ини:
			//точка в ключе — вложенность, литеральная точка — '..' эскейп.
			//Класс гарантированно определён: наш include резолвит $this->config
			$reader = new Core_Ini_Reader;

			$merged = [];
			foreach ($files as $f)
				$merged = array_replace_recursive($merged, (array) $reader->fromFile($f));

			return $this->locale[$lang] = $merged;
		}

		//Секция локали по составному ключу 'zone:type:name' (null — нет).
		//При промахе — ленивая докачка: merge всех файлов по пути секции
		private function section($lang, $key)
		{
			$loc = $this->load($lang) ?: [];
			if (isset($loc[$key]) && is_array($loc[$key])) return $loc[$key];

			$this->loadPath($lang, $key);
			$loc = $this->locale[$lang] ?: [];
			return (isset($loc[$key]) && is_array($loc[$key])) ? $loc[$key] : null;
		}

		//Ленивая подгрузка по пути секции 'a:b:c' — существующие файлы
		//{lang}/a.ini … {lang}/a/b/c.ini сливаются от мелкого к глубокому
		//(глубокий перекрывает), плоские {lang}/*.ini уже загружены eager-ом.
		//Пробы кешируются: повторный промах по секции бесплатен
		private function loadPath($lang, $key)
		{
			$dir   = rtrim($this->config['translate']['folder'] ?? 'app/l10n', '/\\');
			$parts = explode(':', $key);

			//Кандидаты — все префиксы пути секции от мелкого к глубокому
			//({lang}/a/b.ini … {lang}/a/b/c.ini); верхний уровень
			//{lang}/{a}.ini уже покрыт eager-glob'ом {lang}/*.ini
			$files = [];
			for ($n = 2; $n <= count($parts); $n++)
				$files[] = "$dir/$lang/" . implode('/', array_slice($parts, 0, $n)) . '.ini';

			foreach ($files as $file)
			{
				if (isset($this->probed[$file])) continue;
				$this->probed[$file] = true;
				if (!is_file($file)) continue;

				$reader = new Core_Ini_Reader;
				$this->locale[$lang] = array_replace_recursive(
					(array) $this->locale[$lang],
					(array) $reader->fromFile($file)
				);
			}
		}

		//Разрешение ссылки ['key'] → секция; ['key','field'] → поле.
		//Это lookup-режим, не трансформация: промах → null (caller ?? default)
		private function resolveRef($lang, $key, $field = null)
		{
			$sec = $this->section($lang, $key);
			if ($sec === null)
				return $this->miss($key, null, $lang);

			if ($field === null) return $sec;
			return array_key_exists($field, $sec)
				? $sec[$field]
				: $this->miss("$key.$field", null, $lang);
		}

		//Список-ссылка: чистый numeric array, элементы — строки
		private function isRef(array $a)
		{
			return isset($a[0]) && is_string($a[0])
			    && array_keys($a) === range(0, count($a) - 1);
		}

		//Обход маркеров: узел с ключом-маркером получает override полей
		//из секции локали; маркер сохраняется (повторная локализация работает)
		private function walk(array $node, $lang)
		{
			$mkey = $this->config['translate']['key'] ?? 'l10n';

			if (isset($node[$mkey]) && is_string($node[$mkey]))
			{
				$sec = $this->section($lang, $node[$mkey]);
				if ($sec !== null) $node = array_replace_recursive($node, $sec);
				else               $this->miss($node[$mkey], $node, $lang);
			}

			foreach ($node as &$v)
				if (is_array($v)) $v = $this->walk($v, $lang);

			return $node;
		}

		//Auto-обход: значения перечисленных полей заменяются словарём [auto]
		private function autoWalk(array $node, $lang, array $fields)
		{
			foreach ($fields as $f)
				if (isset($node[$f]) && is_string($node[$f]) && $node[$f] !== '')
					$node[$f] = $this->dict($lang, 'auto', $node[$f]);

			foreach ($node as &$v)
				if (is_array($v)) $v = $this->autoWalk($v, $lang, $fields);

			return $node;
		}

		//Словарь ([t]/[auto]): точное совпадение → нормализованное;
		//промах — miss + вернуть оригинал
		private function dict($lang, $section, $value)
		{
			$map = ($this->load($lang) ?: [])[$section] ?? null;
			if (is_array($map) && array_key_exists($value, $map))
				return $map[$value];

			$ix = $this->dictIndex($lang);
			$k  = $this->normKey($value);
			if (isset($ix[$k])) return $ix[$k];

			$this->miss($value, $value, $lang);
			return $value;
		}

		//Индекс словарных секций ([t],[auto]) с ключами,
		//нормализованными по auto_trim/auto_lowercase — строится раз на язык
		private function dictIndex($lang)
		{
			if (!isset($this->dictIndex[$lang]))
			{
				$loc = $this->load($lang) ?: [];
				$ix  = [];
				foreach (['t', 'auto'] as $sec)
					foreach ((array) ($loc[$sec] ?? []) as $k => $v)
						$ix[$this->normKey((string) $k)] = $v;
				$this->dictIndex[$lang] = $ix;
			}
			return $this->dictIndex[$lang];
		}

		//Нормализация ключа словаря по правилам конфига
		private function normKey($v)
		{
			if (!empty($this->config['auto']['trim']))      $v = trim($v);
			if (!empty($this->config['auto']['lowercase'])) $v = mb_strtolower($v);
			return $v;
		}

		//Накопить промах для редактора переводов (при collect.missing).
		//Lookup-режим вернёт null, трансформации — оригинал
		private function miss($key, $orig, $lang)
		{
			if (!empty($this->config['collect']['missing']))
				$this->missing["$lang|$key"] = ['key' => $key, 'orig' => $orig];
			return null;
		}
	}


	# ---------------------------------------------------------------- #
	# --------------[ СОЗДАЕМ И ПОДКЛЮЧАЕМ ИНТЕРФЕЙС ]---------------- #
	# ---------------------------------------------------------------- #

	//Ноль зависимостей: конфиг — чистый массив, локали парсятся напрямую
	return new QL10n($this->config->get(__FILE__));
