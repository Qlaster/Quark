<?php

	/*
	 * objects
	 *
	 * Фасад реализует хранилище объектов, с возможностью группировки по коллекциям.
	 *
	 * Version 1.0
	 * Copyright 2022
	 *
	 *
	 * - Сохранить объект
	 * $APP->objects->set($name, $object);
	 *
	 * - Получить объект
	 * $APP->objects->get($name);
	 *
	 * - Удалить объект
	 * $APP->objects->det($name);
	 *
	 * - Создать коллекцию
	 * $APP->objects->collection('fruits')->create();
	 *
	 * - Положить объект в коллекцию
	 * $APP->objects->collection('fruits')->set('apple', $apple);
	 *
	 * - Получить объект из коллекции
	 * $APP->objects->collection('fruits')->get('apple');
	 *
	 * - Удалить коллекцию
	 * $APP->objects->collection('fruits')->drop();
	 *
	 * - Скопировать коллекцию
	 * $APP->objects->collection('fruits')->copy('newName');
	 *
	 * - Скопировать объект
	 * $APP->objects->collection('fruits')->copy('oldName', 'newName');
	 * $APP->objects->copy('oldName', 'newName');
	 *
	 * - Переименовать коллекцию
	 * $APP->objects->collection('fruits')->rename('newName');
	 *
	 * - Переименовать объект
	 * $APP->objects->collection('fruits')->rename('oldName', 'newName');
	 * $APP->objects->rename('oldName', 'newName');
	 *
	 * Языки документов
	 *
	 * Документ адресуется тройкой (collection, name, lang). lang() — одноразовый
	 * рычаг на следующую операцию: сбрасывается после неё и при collection().
	 * null (не выбран) и '' — базовый документ; массив — цепочка приоритетов
	 * при чтении (первая найденная строка) или список языков при удалении.
	 *
	 * - Перевести / прочитать документ на языке
	 * $APP->objects->collection('pages')->lang('en')->set('about', $doc);
	 * $APP->objects->collection('pages')->lang('en')->get('about'); //нет en → null
	 *
	 * - Каскадное чтение (uk, иначе ru, иначе база)
	 * $APP->objects->collection('pages')->lang(['uk','ru',''])->get('about');
	 *
	 * - Языки документа
	 * $APP->objects->collection('pages')->langs('about');   //['', 'en', ...]
	 *
	 * - Удаление: lang('en')->del — только перевод; lang(['en','uk'])->del —
	 *   перечисленные; del() без lang — вся семья
	 *
	 * Экспорт/импорт
	 *
	 * Дамп — список строк таблицы один в один (все колонки, включая lang):
	 * [{"collection":"pages","name":"about","lang":"","value":"...","date":"..."}]
	 * Формат схемо-толерантен: колонки дампа, отсутствующие в таблице,
	 * игнорируются при импорте.
	 *
	 * - Выгрузить всю базу / коллекцию / документ(ы) / языки
	 * $APP->objects->export();
	 * $APP->objects->collection('pages')->export();
	 * $APP->objects->collection('pages')->export(['about','b']);
	 * $APP->objects->lang('en')->export();
	 *
	 * - Загрузить (перезапись по тройке collection+name+lang)
	 * $APP->objects->import($json);
	 * $APP->objects->import($json, Objects::IMPORT_STRICT); //коллизии → исключение
	 * $APP->objects->import($json, Objects::IMPORT_SKIP);   //существующие пропустить
	*/

	namespace App\Facade;

	# ---------------------------------------------------------------- #
	#                 ЭКСПОРТИРУЕМ 	ИНТЕРФЕЙС                          #
	# ---------------------------------------------------------------- #
	interface QObjectsInterface
	{
		//Устанавливает коллекцию для дальнейшей работы с ней
		public function collection($collection_name);

		//Устанавливает язык следующей операции с объектом
		public function lang($lang = null);

		//Список языков документа (семья переводов)
		public function langs($name);

		// Записать объект
		public function set($name, $value);

		// Получаем объект
		public function get($name);

		// Удалить объект
		public function del($name);

		// Создать коллекцию
		public function create();

		// Удаляем коллекцию
		public function drop();

		// Получить все имена коллекции
		public function names();

		// Получить все записи коллекции
		public function all();

		// Переименовать объект или коллекцию
		public function rename($oldName, $newName);

		// Дублировать коллекцию или объект
		public function copy($nameA, $nameB);
	}

	# ---------------------------------------------------------------- #
	#                 РЕАЛИЗАЦИЯ	ИНТЕРФЕЙСА                         #
	# ---------------------------------------------------------------- #
	class Objects implements QObjectsInterface
	{

		//Хранит параметры конфигурации.
		public 	$config = array();

		//переменная хранит значение текущей коллекции
		private $collection = '';

		//язык следующей операции с объектом (null — не выбран, '' — базовый документ).
		//Одноразовый: сбрасывается на collection() и после каждого get/set/del/all
		private $lang = null;

		//Интерфейс к менеджеру базы
		public $PDO_INTERFACE;

		//Флаги import(): политика при коллизии с существующими документами
		const IMPORT_OVERWRITE = 0;	//перезаписать молча (поведение по умолчанию, легаси)
		const IMPORT_STRICT    = 1;	//коллизии → исключение ДО записи (двухфазно)
		const IMPORT_SKIP      = 2;	//существующие документы пропустить, остальные залить


		/*
		 *
		 * name: Конструктор класса. На входе принимает имена таблиц под контент, в которых будет хранить информацию. Спасибо, кэп!
		 * @param PDO, подключенный к базе
		 * @return void
		 *
		 */
		function __construct($PDO_interface, $Table='items', $TableTimeline='timeline')
		{

			//Если переданный клас не является PDO, то показываем ошибку
			if ( ! ($PDO_interface instanceOf \PDO) )
			{
				//...выбрасываем предупреждение
				trigger_error ( "Переданный интерфейс не является объектом PDO." , E_USER_WARNING );
				return false;
			}

			//Устанавливаем интерфейс к базе данных
			$this->PDO_INTERFACE 	= &$PDO_interface;
			//Переводим в режим предупреждений
			$this->PDO_INTERFACE->setAttribute( \PDO::ATTR_ERRMODE, \PDO::ERRMODE_WARNING );
			//PDO будет возвращать только ассоциативные массивы
			$this->PDO_INTERFACE->setAttribute( \PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
			//NULL преобразовывать в пустые строки.
			$this->PDO_INTERFACE->setAttribute( \PDO::ATTR_ORACLE_NULLS, \PDO::NULL_TO_STRING);
			//Если использовали драйвер sqlite задействуем журнал, что бы увеличить производительность
			if ($this->PDO_INTERFACE->getAttribute( \PDO::ATTR_DRIVER_NAME) == 'sqlite')
				$this->PDO_INTERFACE->exec("PRAGMA journal_mode = wal;");

			//~ $this->PDO_INTERFACE->exec("PRAGMA busy_timeout = 15000;");

			//Объявляем имена таблиц
			$this->Table = $Table;
			//~ $this->Table_Content 	= $Table_Content;

			// Инициализация Timeline
			$this->timeline = new timeline($this->PDO_INTERFACE, $TableTimeline, $Table);

			//Построим таблицы, если их нет
			$this->DBConstruct();
		}



		/*
		 *
		 * name: Создание (подготовка) базы со всеми табличками
		 * @param
		 * @return
		 *
		 */
		public function DBConstruct()
		{
			$this->PDO_INTERFACE->BeginTransaction();

			//Создаем таблицу с перечислением страниц
			$table = $this->Table;

			$stmt = $this->PDO_INTERFACE->prepare(
				"CREATE TABLE IF NOT EXISTS '$table'
					(
						collection,
						name,
						lang,
						value,
						date,
						CONSTRAINT PK_Person PRIMARY KEY (collection, name, lang)
					);
				");

			$stmt->execute();

			//Создаем индекс
			$this->PDO_INTERFACE->prepare("CREATE INDEX IF NOT EXISTS 'index_collection' on '$table' ('collection');")->execute();
			$this->PDO_INTERFACE->prepare("CREATE INDEX IF NOT EXISTS 'index_name'       on '$table' ('name');")->execute();

			//Применяем изменения
			$this->PDO_INTERFACE->Commit();

		}


		//Устанавливает коллекцию для дальнейшей работы с ней
		public function collection($collection_name)
		{
			$this->collection = $collection_name;
			$this->lang = null;
			return $this;
		}

		//Устанавливает язык следующей операции с объектом. Сбрасывается после операции.
		//null — не выбран; 'en' — один язык; ['uk','ru',''] — цепочка приоритетов (первая найденная строка)
		public function lang($lang = null)
		{
			if ($lang !== null)
				$lang = is_array($lang) ? array_map('strval', $lang) : (string) $lang;
			$this->lang = $lang;
			return $this;
		}


		//Сырая строка (collection, name, lang)
		private function row($name, $lang)
		{
			$table = $this->Table;

			$STH = $this->PDO_INTERFACE->prepare("SELECT value FROM '$table' WHERE (collection = :collection and name = :name and lang = :lang);");
			$STH->bindParam(':collection',	$this->collection);
			$STH->bindParam(':name',		$name);
			$STH->bindParam(':lang',		$lang);
			$STH->execute();

			$obj = $STH->fetchAll();

			if (isset($obj[0]))
				return unserialize($obj[0]['value']);
		}


		//Список языков документа (семья переводов): ['', 'en', ...]
		public function langs($name)
		{
			$table = $this->Table;

			$STH = $this->PDO_INTERFACE->prepare("SELECT DISTINCT lang FROM '$table' WHERE (collection = :collection and name = :name) ORDER BY lang;");
			$STH->bindParam(':collection',	$this->collection);
			$STH->bindParam(':name',		$name);
			$STH->execute();

			return array_column($STH->fetchAll(), 'lang');
		}


		//Возвращает список всех коллекций
		public function collection_list()
		{
			$table = $this->Table;

			$STH = $this->PDO_INTERFACE->prepare("SELECT DISTINCT collection FROM '$table'");
			$STH->execute();

			//Приводим к требуемой структуре
			$result = $STH->fetchAll();
			foreach	($result as &$value)
				$value = $value['collection'];

			//Возвращаем полученный массив
			return $result;
		}



		//Записываем объект
		public function set($name, $value)
		{
			$lang         = $this->lang;
			$this->lang   = null;
			$table = $this->Table;

			//Каскад записи невозможен — один документ = одна языковая строка
			if (is_array($lang))
			{
				trigger_error("objects->set: массив языков не принимается, нужен один lang", E_USER_WARNING);
				return false;
			}
			$lang = $lang ?? '';

			// Проверяем: объект существует? → update, иначе → insert
			$existing = $this->row($name, $lang);
			$action   = ($existing !== null) ? 'update' : 'insert';
			$value    = serialize($value);

			// Сохраняем предыдущее состояние
			if ($existing !== null)
			{
				$this->timeline->insert([
					'collection' => $this->collection,
					'name' => $name,
					'lang' => $lang,
					'value' => serialize($existing),
					'action' => $action
				]);
			}

			$STH = $this->PDO_INTERFACE->prepare("REPLACE INTO '$table' ('collection', 'name', 'lang', 'value') VALUES (:collection, :name, :lang, :value)");

			//Заполняем выражение данными
			$STH->bindParam(':collection',	$this->collection);
			$STH->bindParam(':name',		$name);
			$STH->bindParam(':lang',		$lang);
			$STH->bindParam(':value',		$value);

			//Выполняем изменения
			return $STH->execute();
		}


		/*
		 * name: Получаем объект
		 * @param	имя объекта
		 * @return
		 */
		public function get($name)
		{
			$lang       = $this->lang;
			$this->lang = null;

			//Цепочка языков: первая найденная строка — ответ
			foreach (is_array($lang) ? $lang : [$lang ?? ''] as $l)
				if (($v = $this->row($name, $l)) !== null)
					return $v;
		}



		/*
		 * name: Удаляем объект
		 * @param	имя объекта
		 * @return
		 */
		public function del($name)
		{
			$lang         = $this->lang;
			$this->lang   = null;
			$table = $this->Table;

			//null — вся семья; скаляр — один язык; массив — перечисленные языки
			$langs = $lang === null ? null : (array) $lang;
			$in    = $langs === null ? '' : ' and lang IN ('.implode(',', array_fill(0, count($langs), '?')).')';
			$where = $langs === null ? [$this->collection, $name] : array_merge([$this->collection, $name], $langs);

			//Лог удаления по каждой строке
			$STH = $this->PDO_INTERFACE->prepare("SELECT lang, value FROM '$table' WHERE collection = ? and name = ?$in;");
			$STH->execute($where);

			foreach ($STH->fetchAll() as $row)
				$this->timeline->insert([
					'collection' => $this->collection,
					'name' => $name,
					'lang' => $row['lang'],
					'value' => $row['value'],
					'action' => 'delete'
				]);

			$STH = $this->PDO_INTERFACE->prepare("DELETE FROM '$table' WHERE collection = ? and name = ?$in;");
			return $STH->execute($where);
		}


		/*
		 * name: Создаем коллекцию
		 * @param	имя объекта
		 * @return
		 */
		public function create()
		{
			$table = $this->Table;

			$STH = $this->PDO_INTERFACE->prepare("REPLACE INTO '$table' (collection, lang) VALUES (:collection, '')");
			//~ $STH = $this->PDO_INTERFACE->prepare("INSERT INTO '$table' ('collection', 'name', 'value') VALUES (:collection, :name, :value) ON CONFLICT('collection', 'name') DO UPDATE SET value = :value;");
			//~ $STH = $this->PDO_INTERFACE->prepare("INSERT INTO '$table' ('collection', 'name', 'value') VALUES (:collection, :name, :value) ON DUPLICATE KEY UPDATE sent_time = values(sent_time),  status = values(status);");

			//Заполняем выражение данными
			$STH->bindParam(':collection',	$this->collection);

			//Выполняем изменения
			return $STH->execute();
		}

		/*
		 * name: Удаляем коллекцию
		 * @param
		 * @return
		 */
		public function drop()
		{
			$table = $this->Table;

			$STH = $this->PDO_INTERFACE->prepare("DELETE FROM '$table' WHERE (collection = :collection);");

			//Заполняем выражение данными
			$STH->bindParam(':collection',	$this->collection);

			//Выполняем изменения
			$STH->execute();
		}



		/*
		 * name: Получить все имена коллекции
		 * @param	имя объекта
		 * @return
		 */
		public function names()
		{
			$table = $this->Table;

			$STH = $this->PDO_INTERFACE->prepare("SELECT DISTINCT (name) FROM '$table' WHERE (collection = :collection and name IS NOT NULL) ORDER BY name;");

			//Заполняем выражение данными
			$STH->bindParam(':collection',	$this->collection);

			//Выполняем изменения
			$STH->execute();

			//Приводим к требуемой структуре
			$result = $STH->fetchAll();
			foreach	($result as &$value)
				$value = $value['name'];

			return $result;
		}


		/*
		 * name: Переименовать объект или коллекцию
		 * @param string текущее имя объекта или новое имя для коллекции
		 * @param string новое имя объекта
		 * @return
		 */
		public function rename($nameA, $nameB=null)
		{
			$table = $this->Table;

			if (isset($nameA) && isset($nameB)) //Для объекта
			{
				$STH = $this->PDO_INTERFACE->prepare("UPDATE `$table` SET name = :newname WHERE collection = :collection AND name = :oldname;");
				//Заполняем выражение данными
				$STH->bindParam(':collection',	$this->collection);
				$STH->bindParam(':oldname',		$nameA);
				$STH->bindParam(':newname',		$nameB);
			}
			elseif ($this->collection && isset($nameA)) //для коллекции
			{
				$STH = $this->PDO_INTERFACE->prepare("UPDATE `$table` SET collection = :newname WHERE collection = :collection;");
				//Заполняем выражение данными
				$STH->bindParam(':collection',	$this->collection);
				$STH->bindParam(':newname',		$nameA);
			}

			//Выполняем изменения
			return $STH->execute();
		}


		/**
		 * Дублировать коллекцию или объект
		 * @param string текущее имя объекта или новое имя для коллекции
		 * @param string новое имя объекта
		 * @return bool Успешность выполнения операции
		 */
		public function copy($nameA, $nameB=null)
		{
			$table = $this->Table;

			if (isset($nameA) && isset($nameB)) //Для объекта
			{
				//Через нативный интерфейс
				//return $obj = $this->get($nameA) ? $this->set($nameB, $obj) : false;

				$STH = $this->PDO_INTERFACE->prepare("INSERT INTO `$table` (collection, name, lang, value, date)
						SELECT
							collection,
							:nameTarget AS name,
							lang,
							value,
							date
						FROM
							`$table`
						WHERE
							collection = :collection and name = :name");

				//Заполняем выражение данными
				$STH->bindParam(':collection',	$this->collection);
				$STH->bindValue(':name',        $nameA);
				$STH->bindValue(':nameTarget',  $nameB);
			}
			elseif ($this->collection && isset($nameA)) //для коллекции
			{
				$STH = $this->PDO_INTERFACE->prepare("INSERT INTO `$table` (collection, name, lang, value, date)
						SELECT
							:collectionTarget AS collection,
							name,
							lang,
							value,
							date
						FROM
							`$table`
						WHERE
							collection = :collection;");

				//Заполняем выражение данными
				$STH->bindParam(':collection',	     $this->collection);
				$STH->bindValue(':collectionTarget', $nameA);
			}

			return $STH->execute();
		}


		/*
		 * name: Получить все записи коллекции
		 * @param	имя объекта
		 * @return
		 */
		public function all()
		{
			$lang       = $this->lang;
			$this->lang = null;
			$result = [];
			$table = $this->Table;
			$langs = (array) ($lang ?? '');

			//Строки перечисленных языков одним запросом
			$in = implode(',', array_fill(0, count($langs), '?'));
			$STH = $this->PDO_INTERFACE->prepare("SELECT name, lang, value FROM '$table' WHERE collection = ? and name IS NOT NULL and lang IN ($in) ORDER BY name;");
			$STH->execute(array_merge([$this->collection], $langs));

			foreach ($STH->fetchAll() as $r)
				$rows[$r['name']][$r['lang']] = unserialize($r['value']);

			//Первый приоритетный язык каждой семьи — как у цепочного get
			foreach ($rows ?? [] as $name => $family)
				foreach ($langs as $l)
					if (isset($family[$l]))
						{ $result[$name] = $family[$l]; break; }

			return $result;
		}


		/**
		 * Экспортировать коллекцию или объект в формате JSON
		 *
		 * @param string $object Название объекта в коллекции (пустая строка - вся коллекция, указанная через collection())
		 *
		 * @return string JSON-строка с результатом экспорта
		 */
		public function export($names=null): string
		{
			$lang       = $this->lang;
			$this->lang = null;
			$table      = $this->Table;

			//Фильтры: коллекция — курсором collection(), имена — аргументом (строка|массив), языки — ->lang()
			$where  = [];
			$params = [];

			if ($this->collection)
			{
				$where[]  = 'collection = ?';
				$params[] = $this->collection;
			}

			if ($names !== null)
			{
				$names   = (array) $names;
				$where[] = 'name IN ('.implode(',', array_fill(0, count($names), '?')).')';
				$params  = array_merge($params, $names);
			}

			if ($lang !== null)
			{
				$langs   = (array) $lang;
				$where[] = 'lang IN ('.implode(',', array_fill(0, count($langs), '?')).')';
				$params  = array_merge($params, $langs);
			}

			$STH = $this->PDO_INTERFACE->prepare("SELECT * FROM `$table`".($where ? ' WHERE '.implode(' and ', $where) : '')." ORDER BY collection, name, lang;");
			$STH->execute($params);

			return json_encode($STH->fetchAll());
		}

		public function import($datastring, $flags = 0)
		{
			$rows = json_decode($datastring, true);
			if (!is_array($rows)) return false;

			$table = $this->Table;

			//Каждая строка обязана нести collection и name — адрес документа
			foreach ($rows as $i => $row)
				if (!is_array($row) or !array_key_exists('collection', $row) or !array_key_exists('name', $row))
					throw new \Exception("Objects import: строка #$i без collection/name — формат не распознан");

			//Допустимые колонки — из целевой таблицы, не из констант: дамп новее/старше схемы проходит мягко
			$cols = array_flip(array_column($this->PDO_INTERFACE->query("PRAGMA table_info('$table')")->fetchAll(), 'name'));

			//Фаза проверки: при STRICT/SKIP сначала собираем коллизии по (collection, name, lang), ничего не записывая
			if ($flags & (self::IMPORT_STRICT | self::IMPORT_SKIP))
			{
				$collide = [];
				foreach ($rows as $i => $row)
				{
					$this->collection($row['collection']);
					if ($this->row($row['name'], $row['lang'] ?? '') !== null)
						$collide[$i] = "{$row['collection']}.{$row['name']}@".($row['lang'] ?? '');
				}

				//STRICT: есть коллизии — отказ до записи, без частичного импорта
				if ($collide and ($flags & self::IMPORT_STRICT))
					throw new \Exception('Objects import: документы уже существуют — '.implode(', ', $collide));

				//SKIP: вырезаем существующие из входных данных
				if ($collide and ($flags & self::IMPORT_SKIP))
					foreach (array_keys($collide) as $i)
						unset($rows[$i]);
			}

			foreach ($rows as $row)
			{
				$this->collection($row['collection']);

				//Только колонки, существующие в целевой таблице
				$row  = array_intersect_key($row, $cols);
				$keys = array_keys($row);

				//Лог предыдущего состояния — как при set()
				$old = $this->row($row['name'], $row['lang'] ?? '');
				if ($old !== null)
					$this->timeline->insert([
						'collection' => $row['collection'],
						'name' => $row['name'],
						'lang' => $row['lang'] ?? '',
						'value' => serialize($old),
						'action' => 'update'
					]);

				$STH = $this->PDO_INTERFACE->prepare("REPLACE INTO `$table` (".implode(',', $keys).") VALUES (".implode(',', array_fill(0, count($keys), '?')).")");
				$STH->execute(array_values($row));
			}
			return true;
		}
	}


		class timeline
		{
			/** @var \PDO */
			private $PDO;

			/** @var string Таблица истории */
			private $table;

			/** @var string Таблица основных объектов (для restore) */
			private $objectsTable;

			/**
			 * @param \PDO   $PDO
			 * @param string $table        Имя таблицы истории
			 * @param string $objectsTable Имя основной таблицы объектов
			 */
			public function __construct(\PDO $PDO, string $table = 'timeline', string $objectsTable = 'items')
			{
				$this->PDO         = $PDO;
				$this->table       = $table;
				$this->objectsTable = $objectsTable;
				$this->DBConstruct();
			}

			/**
			 * Создаёт таблицу истории и индексы, если их нет
			 */
			private function DBConstruct()
			{
				$t = $this->table;

				//`id`         INTEGER PRIMARY KEY AUTOINCREMENT,
				$this->PDO->exec("CREATE TABLE IF NOT EXISTS `$t` (
					`collection` TEXT,
					`name`       TEXT,
					`lang`       TEXT,
					`value`      TEXT,
					`date`       TEXT,
					`action`     TEXT
				);");

				$this->PDO->exec("CREATE INDEX IF NOT EXISTS 'tl_idx_collection' ON `$t` (`collection`);");
				$this->PDO->exec("CREATE INDEX IF NOT EXISTS 'tl_idx_name'       ON `$t` (`name`);");
				$this->PDO->exec("CREATE INDEX IF NOT EXISTS 'tl_idx_date'       ON `$t` (`date`);");
			}

			/**
			 * Записывает снимок объекта в историю.
			 * Вызывается из методов Objects ДО выполнения изменения.
			 *
			 * @param string      $collection Коллекция
			 * @param string      $name       Имя объекта
			 * @param string|null $value      Сериализованное значение объекта (null — объекта не было)
			 * @param string      $action     Тип действия: insert | update | delete | rename
			 */
			public function insert(array $record): bool
			{
				if (!$record) return false;

				$t    = $this->table;
				$date = date('Y-m-d H:i:s');

				list($collection, $name, $lang, $value, $action) = [$record['collection'], $record['name'], $record['lang'] ?? '', $record['value'], $record['action']];

				$STH = $this->PDO->prepare(
					"INSERT INTO `$t` (collection, name, lang, value, date, action)
					 VALUES (:collection, :name, :lang, :value, :date, :action)"
				);

				$STH->bindValue(':collection', $collection);
				$STH->bindValue(':name',       $name);
				$STH->bindValue(':lang',       $lang);
				$STH->bindValue(':value',      $value);
				$STH->bindValue(':date',       $date);
				$STH->bindValue(':action',     $action);

				return $STH->execute();
			}

			/**
			 * Выборка записей из истории по произвольным условиям.
			 *
			 * Пример:
			 *   $timeline->select(['collection' => 'fruits', 'name' => 'apple'])
			 *   $timeline->select(['collection' => 'fruits', 'action' => 'delete'])
			 *
			 * @param array $where Ассоциативный массив условий [поле => значение]
			 * @return array
			 */
			public function select(array $where): array
			{
				$t          = $this->table;
				$conditions = [];
				$params     = [];

				foreach ($where as $key => $val)
				{
					$conditions[] = "`$key` = :$key";
					$params[":$key"] = $val;
				}

				$sql = "SELECT * FROM `$t`";

				if (!empty($conditions))
					$sql .= " WHERE " . implode(' AND ', $conditions);


				$sql .= " ORDER BY `date` DESC";

				$STH = $this->PDO->prepare($sql);
				$STH->execute($params);

				return $STH->fetchAll(\PDO::FETCH_ASSOC);
			}

			/**
			 * Удаление записей из истории по условиям.
			 *
			 * Пример:
			 *   $timeline->delete(['collection' => 'fruits', 'name' => 'apple'])
			 *   $timeline->delete(['collection' => 'fruits']) // вся история коллекции
			 *
			 * @param array $where Ассоциативный массив условий [поле => значение]
			 * @return bool
			 */
			public function delete(array $where): bool
			{
				$t          = $this->table;
				$conditions = [];
				$params     = [];

				foreach ($where as $key => $val)
				{
					$conditions[] = "`$key` = :$key";
					$params[":$key"] = $val;
				}

				$sql = "DELETE FROM `$t`";

				if (!empty($conditions))
					$sql .= " WHERE " . implode(' AND ', $conditions);


				$STH = $this->PDO->prepare($sql);
				return $STH->execute($params);
			}

			/**
			 * Восстанавливает объект из истории в основную таблицу.
			 *
			 * @param array      $from Условия поиска снимка в истории.
			 *                         Например: ['collection' => 'fruits', 'name' => 'apple', 'date' => '2024-01-15 10:00:00']
			 *                         Берётся первая запись (самая свежая по дате ≤ указанной).
			 * @param array|null $to   Куда восстановить ['collection' => ..., 'name' => ...].
			 *                         Если null — восстанавливает на то же место.
			 * @return bool
			 */
			public function restore(array $from, $to = null): bool
			{
				// Строим запрос с поддержкой диапазона дат (берём снимок НА конкретную дату)
				$t          = $this->table;
				$conditions = [];
				$params     = [];

				foreach ($from as $key => $val)
				{
					if ($key === 'date') {
						// Ищем последний снимок на указанную дату или раньше
						$conditions[] = "`date` <= :date";
						$params[':date'] = $val;
					} else {
						$conditions[] = "`$key` = :$key";
						$params[":$key"] = $val;
					}
				}

				$sql = "SELECT * FROM `$t`";

				if (!empty($conditions))
					$sql .= " WHERE " . implode(' AND ', $conditions);


				$sql .= " ORDER BY `date` DESC LIMIT 1";

				$STH = $this->PDO->prepare($sql);
				$STH->execute($params);
				$record = $STH->fetch(\PDO::FETCH_ASSOC);

				if (!$record)
					return false; // Снимок не найден


				// Определяем целевые collection, name и lang
				$targetCollection = $to['collection'] ?? $record['collection'];
				$targetName       = $to['name']       ?? $record['name'];
				$targetLang       = $to['lang']       ?? $record['lang'];

				// Восстанавливаем объект в основную таблицу
				$itemsTable = $this->objectsTable;
				$STH = $this->PDO->prepare(
					"REPLACE INTO `$itemsTable` (collection, name, lang, value) VALUES (:collection, :name, :lang, :value)"
				);
				$STH->bindValue(':collection', $targetCollection);
				$STH->bindValue(':name',       $targetName);
				$STH->bindValue(':lang',       $targetLang);
				$STH->bindValue(':value',      $record['value']);

				return $STH->execute();
			}
		}




	# ---------------------------------------------------------------- #
	# --------------[ СОЗДАЕМ И ПОДКЛЮЧАЕМ ИНТЕРФЕЙС ]---------------- #
	# ---------------------------------------------------------------- #
	$config = $this->config->get(__FILE__);
	return new Objects( new \PDO($config['db']['pdo']), $config['table']['items'], $config['table']['timeline'] );


