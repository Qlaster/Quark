<?php

	/*
	 *
	 * TODO: Filtered . is name fields
	 *
	 *
	 */
	namespace unit\catalog;

	# ---------------------------------------------------------------- #
	#                 ЭКСПОРТИРУЕМ 	ИНТЕРФЕЙС                          #
	# ---------------------------------------------------------------- #
	interface QCatalogInterface
	{
		//Подключиться к каталогу (получить информацию о подключении)
		public function get($catalog);

		// Вернет весь список доступных каталогов
		public function listing();

		//Создание каталога
		public function create($catalog);

		//Удаление каталога
		public function delete($name);

		//Обновление каталога или его полей
		public function update($catalog);

		//Получить доступ к элементам каталога для низкоуровневого отбора (вернет ORM)
		public function items($catalog);

		//Посмотреть содержимое каталога c учетом правил отбора
		public function view($catalog, $params);
	}



	# ---------------------------------------------------------------- #
	#                 РЕАЛИЗАЦИЯ   ИНТЕРФЕЙСА                          #
	# ---------------------------------------------------------------- #
	class SimpleCatalog implements QCatalogInterface
	{
		private $dbInterface;		//ИНтерфейс ORM к базе данных
		private $configInterface;	//Интерфейс к конфигу
		public  $lastQuery;			//Последние заправшиваемые параметры

		function __construct($dbInterface, $configInterface)
		{
			$this->dbInterface     = $dbInterface;
			$this->configInterface = $configInterface;
		}

		public function config($newConfig = null)
		{
			if ($newConfig)
				return $this->configInterface->set($newConfig);

			return (array) $this->configInterface->get();
		}

		public function patterns()
		{
			return $this->config()['patterns'];
		}

		public function listing()
		{
			return (array) $this->config()['list'];
		}


		public function items($catalogName=null, $params=[])
		{
			$catalog = $this->get($catalogName);
			$orm = $this->dbInterface->connect($catalog['db'])->table($catalog['table']);

			$groupby = $params['groupby'] ? $params['groupby'] : $catalog['events']['view']['groupby'] ?? null;
			$orderby = $params['orderby'] ? $params['orderby'] : $catalog['events']['view']['orderby'] ?? null;
			$where   = $params['where']   ? $params['where']   : $catalog['events']['view']['where']   ?? null;

			$limit   = $params['limit']   ? $params['limit']   : $this->config()['view']['limit'] ?? null;
			$offset  = $params['offset']  ? $params['offset']  : null;
			$like    = $params['like']    ? $params['like']    : null;

			is_array($where)   ? $orm->where  (... $where)   : $orm->where($where);
			is_array($orderby) ? $orm->orderby(... $orderby) : $orm->orderby($orderby);
			is_array($groupby) ? $orm->groupby(... $where)   : $orm->groupby($groupby);
			        ($like)    ? $orm->like   (    $like)    : null;

			if ($limit or $offset) $orm->limit($limit, $offset);

			//Поля, требующие представления в виде массива (распаковки json)
			foreach ((array) $catalog['field'] as $fieldName => $field)
				if (in_array($field['type'], ['files'])) $files[] = $fieldName;
			$orm->json($files??[]);

			//Закешем последние параметры запроса, т.к. фасад в одностороннем парядке может поправить входные параметры
			//на свое усмотрение (например, если они некоректны или противоречат ограничениям в конфиге)
			$this->lastQuery = ['catalog'=>$catalogName, 'groupby'=>$groupby, 'orderby'=>$orderby, 'where'=>$where, 'like'=>$like, 'limit'=>$limit, 'offset'=>$offset];

			return $orm;
		}

		/*
		 *
		 * Получить содержимое каталога используя все правила и настройки
		 * name: QCatalogInterface::view
		 * @param
		 * @return listing items
		 *
		 */
		public function view($catalogName=null, $params=[])
		{
			$catalog = $this->get($catalogName);

			//Укажем актуальные поля (если указали - возмем те что указали, если нет - из конфига. Иначе - все что есть)
			//TODO: тут бы тестами нормально покрыть
			$column  = $params['column'] ? is_string($column) ? explode(',', $column) : (array)$column  : $catalog['events']['view']['column']  ?? '';

			//Название каталога
			$catalog['name']   = $catalogName;
			//Получим актуальные поля
			$catalog['field']  = $catalog['field'] ?? $this->fields($catalogName);
			//Запросим содержимое
			$catalog['list']   = $this->items($catalogName, $params)->select($column);
			//количество актуальных записей
			$catalog['count']  = $this->items($catalogName, $params)->count();
			$catalog['limit']  = $this->lastQuery['limit'];
			$catalog['offset'] = $this->lastQuery['offset'];

			//TODO: тут бы тестами нормально покрыть: Отфильтруем заявленные поля каталога, до фактически запрошенных
			if ($column)
				$catalog['field'] = array_intersect_key($column, $catalog['field']);

			return $catalog;
		}

		public function get($catalogName=null)
		{
			$connectRecord = $this->config()['list'][$catalogName];
			if (!$connectRecord)          throw new Exception("Not connection name", 1);
			if (!$connectRecord['db'])    throw new Exception("Missing db name to connect $catalogName", 2);
			if (!$connectRecord['table']) throw new Exception("Missing table name to connect $catalogName", 3);

			$connectRecord['name'] = $catalogName;

			//Проверим наличие директорий
			if (!$connectRecord['folder'] and $this->config()['upload']['folder'])
				$connectRecord['folder'] = $this->config()['upload']['folder'].DIRECTORY_SEPARATOR.$catalogName;

			return $connectRecord;
		}

		public function fields($catalogName=null)
		{
			$catalog = $this->config()['list'][$catalogName];
			if (!$catalog) return null;

			$actualColumns = $this->dbInterface->connect($catalog['db'])->table($catalog['table'])->columns();
			if (!$catalog['field']) return $actualColumns;

			//Вернем только те ключи, которые существуют в таблице
			$actualColumns = array_intersect_key($catalog['field'], $actualColumns);

			foreach ($actualColumns as &$column)
				if ($column['type'] == 'select')
				{
					$column['source'] = explode('.', $column['source']);

					if (count($column['source']) == 2) $column['source'] = $this->dbInterface->connect($catalog['db'])->table($column['source'][0])->select($column['source'][1]);
					if (count($column['source']) == 1) $column['source'] = $this->dbInterface->connect($catalog['db'])->table($catalog['table'])->select($column['source'][0]);

					if (is_array($column['source']))
						foreach ($column['source'] as &$value)
							$value = current($value);
				}

			return $actualColumns;
			//~ return $catalog['field'] ? array_keys($catalog['field']) : array_keys($this->dbInterface->connect($catalog['db'])->table($catalog['table'])->columns());
		}

		public function create($catalog)
		{
			$this->catalogValidate($catalog);

			$name = $catalog['name'];

			$config = $this->config();
			if ($config['list'][$name]) throw new Exception("Catalog is exists", 10);
			$config['list'][$name] = $catalog;
			$this->config($config);
		}

		public function update($catalog)
		{
			$this->catalogValidate($catalog);
			$config = $this->config();
			$config['list'][$catalog['name']] = $catalog;
			$this->config($config);
		}

		public function delete($name)
		{
			$config = $this->config();
			unset($config['list'][$name]);
			$this->config($config);
		}

		private function catalogValidate($catalog)
		{
			//Фильтрация вводных данных
			if (!$catalog['name']  = trim($catalog['name']))  throw new Exception("Not corrent catalog name", 4);
			if (!$catalog['db']    = trim($catalog['db']))    throw new Exception("Not corrent db name", 5);
			if (!$catalog['table'] = trim($catalog['table'])) throw new Exception("Not corrent table name", 6);
			//~ if (!$catalog['field'] or !is_array($catalog['field'])) throw new Exception("Not corrent fields", 7);

			//Контроль состояния БД
			if (!$this->dbInterface->connect($catalog['db'])) throw new Exception("DB not found", 8);
			if (!$this->dbInterface->connect($catalog['db'])->table($catalog['table'])) throw new Exception("DB not found", 9);

			return true;
		}

	}

	# ---------------------------------------------------------------- #
	# --------------[ СОЗДАЕМ И ПОДКЛЮЧАЕМ ИНТЕРФЕЙС ]---------------- #
	# ---------------------------------------------------------------- #
	return new SimpleCatalog($this->db, $this->config);
