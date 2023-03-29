<?php
/*
 *
 * TODO: Filtered . is name fields
 *
 *
 */


	# ---------------------------------------------------------------- #
	#                 ЭКСПОРТИРУЕМ 	ИНТЕРФЕЙС                          #
	# ---------------------------------------------------------------- #
	interface QCatalogInterface
	{
		//Подключиться к каталогу
		public function get($catalog);

		// Вернет весь список доступных каталогов
		public function listing();

		//Создание каталога
		public function create($catalog);

		//Удаление каталога
		public function delete($name);

		//Обновление каталога или его полей
		public function update($catalog);
	}



	# ---------------------------------------------------------------- #
	#                 РЕАЛИЗАЦИЯ   ИНТЕРФЕЙСА                          #
	# ---------------------------------------------------------------- #
	class SimpleCatalog implements QCatalogInterface
	{
		private $dbInterface;
		private $configInterface;

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


		public function items($catalogName=null)
		{
			$catalog = $this->get($catalogName);
			return $this->dbInterface->connect($catalog['db'])->table($catalog['table']);
		}

		public function get($catalogName=null)
		{
			$connectRecord = $this->config()['list'][$catalogName];
			if (!$connectRecord) throw new Exception("Not connection name", 1);
			if (!$connectRecord['db']) throw new Exception("Missing db name to connect $catalogName", 2);
			if (!$connectRecord['table']) throw new Exception("Missing table name to connect $catalogName", 3);

			//Проверим наличие директорий
			if (!$connectRecord['folder'] and $this->config()['settings']['folder'])
				$connectRecord['folder'] = $this->config()['settings']['folder'].DIRECTORY_SEPARATOR.$catalogName;

			return $connectRecord;
		}

		public function fields($catalogName=null)
		{
			$catalog = $this->config()['list'][$catalogName];
			if (!$catalog) return null;

			$actualColumns = $this->dbInterface->connect($catalog['db'])->table($catalog['table'])->columns();
			if (!$catalog['field']) return $actualColumns;

			//Вернем только те ключи, которые существуют в таблице
			return array_intersect_key($actualColumns, $catalog['field']);

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
