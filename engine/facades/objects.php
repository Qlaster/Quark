<?php

	/*
	 * objects
	 *
	 * Механика для работы с адресной строкой, страницами и ссылками
	 * Поддерживает редирект, псевдонимы, маршруты и маски роутинга
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
	*/

	namespace unit\objects;

	# ---------------------------------------------------------------- #
	#                 ЭКСПОРТИРУЕМ 	ИНТЕРФЕЙС                          #
	# ---------------------------------------------------------------- #
	interface QObjectsInterface
	{
		//Устанавливает коллекцию для дальнейшей работы с ней
		public function collection($collection_name);

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
	}

	# ---------------------------------------------------------------- #
	#                 РЕАЛИЗАЦИЯ	ИНТЕРФЕЙСА                         #
	# ---------------------------------------------------------------- #
	class Objects implements QObjectsInterface
	{

		//Хранит параметры конфигурации.
		public 	$config = array();

		//переменная хранит значение текущей коллекции
		private $current_collection = '';

		//Интерфейс к менеджеру базы
		public $PDO_INTERFACE;


		/*
		 *
		 * name: Конструктор класса. На входе принимает имена таблиц под контент, в которых будет хранить информацию. Спасибо, кэп!
		 * @param PDO, подключенный к базе
		 * @return void
		 *
		 */
		function __construct($PDO_interface, $Table='items', $Table_Content='content')
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
						value,
						date,
						CONSTRAINT PK_Person PRIMARY KEY (collection, name)
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
			$this->current_collection = $collection_name;
			return $this;
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
			$table = $this->Table;
			$value = serialize($value);

			$STH = $this->PDO_INTERFACE->prepare("REPLACE INTO '$table' ('collection', 'name', 'value') VALUES (:collection, :name, :value)");

			//Заполняем выражение данными
			$STH->bindParam(':collection',	$this->current_collection);
			$STH->bindParam(':name',		$name);
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
			$table = $this->Table;

			$STH = $this->PDO_INTERFACE->prepare("SELECT value FROM '$table' WHERE (collection = :collection and name = :name);");

			//Заполняем выражение данными
			$STH->bindParam(':collection',	$this->current_collection);
			$STH->bindParam(':name',		$name);

			//Выполняем изменения
			$STH->execute();

			//Запрашиваем объект
			$obj = $STH->fetchAll();

			if (isset($obj[0]))
				return unserialize($obj[0]['value']);
		}



		/*
		 * name: Удаляем объект
		 * @param	имя объекта
		 * @return
		 */
		public function del($name)
		{
			$table = $this->Table;

			$STH = $this->PDO_INTERFACE->prepare("DELETE FROM '$table' WHERE (collection = :collection and name = :name);");

			//Заполняем выражение данными
			$STH->bindParam(':collection',	$this->current_collection);
			$STH->bindParam(':name',		$name);

			//Выполняем изменения
			$STH->execute();
		}


		/*
		 * name: Создаем коллекцию
		 * @param	имя объекта
		 * @return
		 */
		public function create()
		{
			$table = $this->Table;

			$STH = $this->PDO_INTERFACE->prepare("REPLACE INTO '$table' (collection) VALUES (:collection)");
			//~ $STH = $this->PDO_INTERFACE->prepare("INSERT INTO '$table' ('collection', 'name', 'value') VALUES (:collection, :name, :value) ON CONFLICT('collection', 'name') DO UPDATE SET value = :value;");
			//~ $STH = $this->PDO_INTERFACE->prepare("INSERT INTO '$table' ('collection', 'name', 'value') VALUES (:collection, :name, :value) ON DUPLICATE KEY UPDATE sent_time = values(sent_time),  status = values(status);");

			//Заполняем выражение данными
			$STH->bindParam(':collection',	$this->current_collection);

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
			$STH->bindParam(':collection',	$this->current_collection);

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

			$STH = $this->PDO_INTERFACE->prepare("SELECT (name) FROM '$table' WHERE (collection = :collection and name IS NOT NULL);");

			//Заполняем выражение данными
			$STH->bindParam(':collection',	$this->current_collection);

			//Выполняем изменения
			$STH->execute();

			//Приводим к требуемой структуре
			$result = $STH->fetchAll();
			foreach	($result as &$value)
				$value = $value['name'];

			return $result;
		}



		/*
		 * name: Получить все записи коллекции
		 * @param	имя объекта
		 * @return
		 */
		public function all()
		{
			$table = $this->Table;

			$STH = $this->PDO_INTERFACE->prepare("SELECT name, value FROM '$table' WHERE (collection = :collection and name IS NOT NULL);");

			//Заполняем выражение данными
			$STH->bindParam(':collection',	$this->current_collection);

			//Выполняем изменения
			$STH->execute();

			//Приводим к требуемой структуре
			$raw = $STH->fetchAll();
			foreach	($raw as $record)
				$result[$record['name']] = unserialize($record['value']);

			return (array) $result;
		}


		//Экспортировать все объекты в строку
		public function export()
		{
			$result = [];
			foreach ($this->collection_list() as $colindex => $collection)
			{
				$result[$collection] = $this->collection($collection)->all();
			}
			return json_encode($result);
		}


		public function import($datastring)
		{
			$collections = json_decode($datastring, true);
			if (!$collections) return false;

			foreach ($collections as $collectionName => $objects)
			{
				foreach ($objects as $name => $object)
				{
					$this->collection($collectionName)->set($name, $object);
				}
			}
			return true;
		}
	}



	# ---------------------------------------------------------------- #
	# --------------[ СОЗДАЕМ И ПОДКЛЮЧАЕМ ИНТЕРФЕЙС ]---------------- #
	# ---------------------------------------------------------------- #
	$config = $this->config->get(__FILE__);
	return new Objects( new \PDO($config['db']['pdo']), $config['table']['items'] );


