<?php

	/*
	 *
	 * TODO: Filtered . is name fields
	 *
	 *
	 */
	namespace App\Facade;

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

		/*
		 *
		 * Подключить интерфейсы БД и конфигурации
		 * name: SimpleCatalog::__construct
		 * @param dbInterface, configInterface
		 * @return void
		 *
		 */
		function __construct($dbInterface, $configInterface)
		{
			$this->dbInterface     = $dbInterface;
			$this->configInterface = $configInterface;
		}

		/*
		 *
		 * Прочитать или записать конфигурацию каталогов (catalog.ini)
		 * name: SimpleCatalog::config
		 * @param newConfig — полный массив конфигурации для записи
		 * @return массив конфигурации / результат записи
		 *
		 */
		public function config($newConfig = null)
		{
			if ($newConfig)
				return $this->configInterface->set($newConfig);

			return (array) $this->configInterface->get();
		}

		/*
		 *
		 * Подписи типов полей для админки (секция [patterns])
		 * name: SimpleCatalog::patterns
		 * @return массив "тип => подпись"
		 *
		 */
		public function patterns()
		{
			return $this->config()['patterns'];
		}

		/*
		 *
		 * Описания всех каталогов (секция [list])
		 * name: QCatalogInterface::listing
		 * @return массив "имя => описание каталога"
		 *
		 */
		public function listing()
		{
			return (array) $this->config()['list'];
		}


		/*
		 *
		 * Низкоуровневый доступ к записям каталога.
		 * Применяет к ORM параметры $params (where/orderby/groupby/like/limit/offset),
		 * по умолчанию — events.view.* из описания каталога.
		 * Вернет ORM — запрос выполняется дальше: select()/update()/delete()/...
		 * name: QCatalogInterface::items
		 * @param catalogName, params
		 * @return ORM
		 *
		 */
		public function items($catalogName=null, $params=[])
		{
			$catalog = $this->get($catalogName);
			$orm = $this->dbInterface->connect($catalog['db'])->table($catalog['table']);

			$groupby = $params['groupby'] ? $params['groupby'] : $catalog['events']['view']['groupby'] ?? null;
			$orderby = $params['orderby'] ? $params['orderby'] : $catalog['events']['view']['orderby'] ?? null;
			$where   = $params['where']   ? $params['where']   : $catalog['events']['view']['where']   ?? null;

			//~ $limit   = $params['limit']   ? $params['limit']   : $this->config()['view']['limit'] ?? null;
			$limit   = $params['limit']   ? $params['limit']   : null;
			$offset  = $params['offset']  ? $params['offset']  : null;
			$like    = $params['like']    ? $params['like']    : null;

			is_array($where)   ? $orm->where  (... $where)   : $orm->where($where);
			is_array($orderby) ? $orm->orderby(... $orderby) : $orm->orderby($orderby);
			is_array($groupby) ? $orm->groupby(... $groupby) : $orm->groupby($groupby);
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
			//~ $column  = $params['column'] ? is_string($column) ? explode(',', $column) : (array)$column  : $catalog['events']['view']['column']  ?? '';

			$column = $params['column'] ? $params['column'] : $catalog['events']['view']['column']??'';
			if ($column && is_string($column) && $column = array_map('trim', explode(',', $column)))
				$column = array_combine($column, $column);

			//Название каталога
			$catalog['name']   = $catalogName;

			//Всегда вызываем fields() — он обогащает поля типа relation метаданными ['relation']
			$catalog['field']  = $this->fields($catalogName);

			$params['limit'] = $params['limit'] ?? $this->config()['view']['limit'] ?? null;

			//Запросим содержимое
			$catalog['list']   = $this->items($catalogName, $params)->select($column);
			//количество актуальных записей
			$catalog['count']  = $this->items($catalogName, $params)->count();
			$catalog['limit']  = $this->lastQuery['limit'];
			$catalog['offset'] = $this->lastQuery['offset'];

			//TODO: тут бы тестами нормально покрыть: Отфильтруем заявленные поля каталога, до фактически запрошенных
			if ($column)
				$catalog['field'] = array_intersect_key($catalog['field'], $column);

			// Разрешаем relation-поля: заменяем сырой ID на массив ['id' => ..., 'label' => ...].
			// Пропускаем поля с директивой ignore = true — они остаются как есть (сырой ID).
			foreach ($catalog['field'] as $fieldName => $field)
			{
				if ($field['type'] !== 'relation' || !$field['relation']) continue;
				// Директива ignore = true отключает подстановку для этого поля
				if ($field['ignore']) continue;

				$relation = $field['relation'];

				// Собираем уникальные непустые ID из текущей страницы записей.
				// array_key_column переиндексирует массив по $fieldName — дубликаты схлопываются.
				$ids = array_filter(array_keys(
					array_key_column($fieldName, (array) $catalog['list'])
				));

				if ($ids)
				{
					$rows = $this->dbInterface->connect($relation['db'])
						->table($relation['table'])
						->where(['id' => $ids])
						->select(['id', $relation['column']]);

					$map = array_column((array) $rows, $relation['column'], 'id');
				}

				// Заменяем сырой ID на массив ['id' => ..., 'label' => ...]
				foreach ($catalog['list'] as &$record)
					$record[$fieldName] = [
						'id'    => $record[$fieldName],
						'label' => $map[$record[$fieldName]] ?? null,
					];
				unset($record);
			}

			return $catalog;
		}

		/*
		 *
		 * Описание каталога из секции [list]: подключение, таблица, поля.
		 * Дополняет запись полем name и folder (по умолчанию upload/folder/<имя>)
		 * name: QCatalogInterface::get
		 * @param catalogName
		 * @return массив описания каталога
		 *
		 */
		public function get($catalogName=null)
		{
			$connectRecord = $this->config()['list'][$catalogName];
			if (!$connectRecord)          throw new \Exception("Not connection name", 1);
			if (!$connectRecord['db'])    throw new \Exception("Missing db name to connect $catalogName", 2);
			if (!$connectRecord['table']) throw new \Exception("Missing table name to connect $catalogName", 3);

			$connectRecord['name'] = $catalogName;

			//Проверим наличие директорий
			if (!$connectRecord['folder'] and $this->config()['upload']['folder'])
				$connectRecord['folder'] = $this->config()['upload']['folder'].DIRECTORY_SEPARATOR.$catalogName;

			return $connectRecord;
		}

		/*
		 *
		 * Поля каталога, реально существующие в таблице БД.
		 * Обогащает select-поля списками значений, а relation-поля —
		 * метаданными связи ['relation' => db/table/column]
		 * name: SimpleCatalog::fields
		 * @param catalogName
		 * @return массив описаний полей
		 *
		 */
		public function fields($catalogName=null)
		{
			$catalog = $this->config()['list'][$catalogName];
			if (!$catalog) return null;

			$actualColumns = $this->dbInterface->connect($catalog['db'])->table($catalog['table'])->columns();
			if (!$catalog['field']) return $actualColumns;

			//Вернем только те ключи, которые существуют в таблице
			$actualColumns = array_intersect_key($catalog['field'], $actualColumns);

			foreach ($actualColumns as &$column)
			{
				if ($column['type'] == 'select')
				{
					$column['source'] = explode('::', $column['source']);

					if (count($column['source']) == 2) $column['source'] = $this->dbInterface->connect($catalog['db'])->table($column['source'][0])->select($column['source'][1]);
					if (count($column['source']) == 1) $column['source'] = $this->dbInterface->connect($catalog['db'])->table($catalog['table'])->select($column['source'][0]);

					if (is_array($column['source']))
						foreach ($column['source'] as &$value)
							$value = current($value);
				}
				elseif ($column['type'] == 'relation')
				{
					// Разбираем source по разделителю '::'.
					// Форматы:
					//   "table::column"              — таблица в БД текущего каталога
					//   "catalog::table::column"     — таблица в БД другого каталога (или явное имя DB-соединения)
					$parts = explode('::', $column['source']);

					if (count($parts) === 3)
					{
						// Пробуем найти каталог с именем $parts[0] — если найдём, берём его DB
						$target = null;
						try { $target = $this->get($parts[0]); } catch (\Exception $e) {}

						$column['relation'] = [
							'db'     => $target ? $target['db'] : $parts[0],
							'table'  => $parts[1],
							'column' => $parts[2],
						];
					}
					else
					{
						// Двухчастный формат: таблица в БД текущего каталога
						$column['relation'] = [
							'db'     => $catalog['db'],
							'table'  => $parts[0],
							'column' => $parts[1] ?? 'id',
						];
					}
				}
			}

			return $actualColumns;
			//~ return $catalog['field'] ? array_keys($catalog['field']) : array_keys($this->dbInterface->connect($catalog['db'])->table($catalog['table'])->columns());
		}

		/*
		 *
		 * Права доступа к записям каталога (секции [access]/[denied]).
		 * Вернет непривязанный QCatalogAccess — дальше ->as($login),
		 * без логина это субъект "гость": совпадают только *-маски
		 * name: SimpleCatalog::access
		 * @param catalogName
		 * @return QCatalogAccess
		 *
		 */
		public function access($catalogName=null)
		{
			return new QCatalogAccess($this->config(), $catalogName);
		}

		/*
		 *
		 * Создать описание каталога в конфигурации
		 * name: QCatalogInterface::create
		 * @param catalog — массив описания каталога
		 * @return результат записи конфигурации
		 *
		 */
		public function create($catalog)
		{
			$this->catalogValidate($catalog);

			$name = $catalog['name'];

			$config = $this->config();
			if ($config['list'][$name]) throw new \Exception("Catalog is exists", 10);
			$config['list'][$name] = $catalog;
			$this->config($config);
		}

		/*
		 *
		 * Обновить описание каталога в конфигурации.
		 * Перезаписывает весь [list].<name> — поля описания, не входящие
		 * в $catalog, будут потеряны
		 * name: QCatalogInterface::update
		 * @param catalog — массив описания каталога
		 * @return результат записи конфигурации
		 *
		 */
		public function update($catalog)
		{
			$this->catalogValidate($catalog);
			$config = $this->config();
			$config['list'][$catalog['name']] = $catalog;
			$this->config($config);
		}

		/*
		 *
		 * Удалить описание каталога из конфигурации
		 * name: QCatalogInterface::delete
		 * @param name — имя каталога
		 * @return результат записи конфигурации
		 *
		 */
		public function delete($name)
		{
			$config = $this->config();
			unset($config['list'][$name]);
			$this->config($config);
		}

		//Валидация описания каталога перед записью: имя/БД/таблица + существование подключения
		private function catalogValidate($catalog)
		{
			//Фильтрация вводных данных
			if (!$catalog['name']  = trim($catalog['name']))  throw new \Exception("Not corrent catalog name", 4);
			if (!$catalog['db']    = trim($catalog['db']))    throw new \Exception("Not corrent db name", 5);
			if (!$catalog['table'] = trim($catalog['table'])) throw new \Exception("Not corrent table name", 6);
			//~ if (!$catalog['field'] or !is_array($catalog['field'])) throw new Exception("Not corrent fields", 7);

			//Контроль состояния БД
			if (!$this->dbInterface->connect($catalog['db'])) throw new \Exception("DB not found", 8);
			if (!$this->dbInterface->connect($catalog['db'])->table($catalog['table'])) throw new \Exception("DB not found", 9);

			return true;
		}

	}



	# ---------------------------------------------------------------- #
	#              ОЦЕНКА ПРАВ ДОСТУПА К КАТАЛОГУ                        #
	# ---------------------------------------------------------------- #
	/*
	 * QCatalogAccess — права субъекта к записям каталога из секций
	 * [access]/[denied] конфигурации каталогов.
	 *
	 *	$ACC = $APP->catalog->access('books')->as($login);   //null = гость
	 *
	 *	$ACC['update']            //bool — операция разрешена
	 *	$ACC['where']['update']   //sql|null — ограничение записей
	 *	$ACC['fields']['update']  //[маски]|null — разрешённые поля
	 *	$ACC['denied']['update']  //[маски] — запрещённые поля
	 *	$ACC->filterRecord('update', $data)   //вырезать запрещённые поля
	 *
	 * Семантика:
	 *	— нет ни [access] ни [denied] → всё открыто (легаси)
	 *	— [access] задан → whitelist: субъект без совпадений = запрет
	 *	— [denied] первым: deny без where закрывает операцию целиком,
	 *	  с where — только совпадающие записи
	 *	— совпавшие маски субъекта объединяются (union)
	 *	— where.* и where.<op> одного правила складываются через AND
	 *	— fields.<op> перекрывает fields.* для этой операции
	 */
	class QCatalogAccess implements \ArrayAccess
	{
		const OPS = ['select', 'insert', 'update', 'delete', 'replace'];

		public  $dropped = [];	//поля, отрезанные последним filterRecord
		private $catalog;
		private $sections = [];	//access/denied → субъект-маска → правило
		private $result   = [];	//op => bool + 'where'/'fields'/'denied' => op => ...

		/*
		 *
		 * Собрать правила каталога из секций [access]/[denied] конфигурации.
		 * Ключи секций — маски имени каталога (books, news-*, *)
		 * name: QCatalogAccess::__construct
		 * @param config — вся конфигурация каталогов, catalog — имя каталога
		 * @return void
		 *
		 */
		function __construct(array $config, $catalog)
		{
			$this->catalog = $catalog;

			//ключи секций — маски имени каталога;
			//правила одного субъекта из разных масок каталога собираются списком — union через eat()
			foreach (['access', 'denied'] as $sec)
				foreach ((array) ($config[$sec] ?? []) as $mask => $subjects)
					if (fnmatch($mask, (string) $catalog, FNM_CASEFOLD))
						foreach ((array) $subjects as $subject => $rule)
							$this->sections[$sec][$subject][] = $rule;

			$this->as(null);
		}

		/*
		 *
		 * Привязать субъекта и вычислить его права.
		 * null — гость, совпадают только *-маски.
		 * Совпавшие правила объединяются: операции и поля — union,
		 * where.* и where.<op> одного правила — через AND,
		 * между правилами — OR; запреты [denied] вычитаются сверху
		 * name: QCatalogAccess::as
		 * @param login — логин субъекта
		 * @return QCatalogAccess
		 *
		 */
		public function as($login)
		{
			$login = $login === null ? '' : mb_strtolower($login);
			$access = $deny = [];

			foreach ((array) ($this->sections['access'] ?? []) as $s => $rules)
				if (fnmatch($s, $login, FNM_CASEFOLD))
					foreach ($rules as $r) $this->eat((array) $r, $access);
			foreach ((array) ($this->sections['denied'] ?? []) as $s => $rules)
				if (fnmatch($s, $login, FNM_CASEFOLD))
					foreach ($rules as $r) $this->eat((array) $r, $deny);

			$res = ['where'=>[], 'fields'=>[], 'denied'=>[]];

			//легаси: политики не объявлены → всё открыто
			if (!isset($this->sections['access']) and !isset($this->sections['denied']))
			{
				foreach (self::OPS as $op)
				{
					$res[$op] = true;
					$res['where'][$op] = $res['fields'][$op] = null;
					$res['denied'][$op] = [];
				}
			}
			else foreach (self::OPS as $op)
			{
				//[access] есть → whitelist; deny без where закрывает операцию целиком
				$res[$op] = (isset($this->sections['access']) ? isset($access['ops'][$op]) : true)
				         && empty($deny['hard'][$op]);

				$res['fields'][$op] = $access['fields'][$op] ?? null;
				$res['denied'][$op] = $deny['fields'][$op]  ?? [];

				//композит: ((allow) OR ...) AND NOT((deny) OR ...);
				//безусловный грант (правило без where) снимает ограничения записей
				$res['where'][$op] = implode(' AND ', array_filter([
					empty($access['hard'][$op]) && !empty($access['where'][$op])
						? '('.implode(' OR ', $access['where'][$op]).')' : null,
					empty($deny['where'][$op]) ? null : 'NOT ('.implode(' OR ', $deny['where'][$op]).')',
				])) ?: null;
			}

			$this->result = $res;
			return $this;
		}

		/*
		 *
		 * Разрешено ли поле для операции: совпадение с allow-масками
		 * (null — все разрешены) минус совпадение с deny-масками
		 * name: QCatalogAccess::fieldAllowed
		 * @param op — операция, field — имя поля
		 * @return bool
		 *
		 */
		public function fieldAllowed($op, $field)
		{
			$allow = $this->result['fields'][$op] ?? null;
			return ($allow === null or $this->any($allow, $field))
			    && !$this->any($this->result['denied'][$op] ?? [], $field);
		}

		/*
		 *
		 * Оставить в данных только разрешённые для операции поля.
		 * Отрезанные ключи складываются в ->dropped
		 * name: QCatalogAccess::filterRecord
		 * @param op — операция, data — запись (поле => значение)
		 * @return отфильтрованная запись
		 *
		 */
		public function filterRecord($op, array $data)
		{
			$this->dropped = [];
			foreach (array_keys($data) as $field)
				if (!$this->fieldAllowed($op, $field))
				{
					$this->dropped[] = $field;
					unset($data[$field]);
				}
			return $data;
		}

		//ArrayAccess: $ACC['update'] → bool, $ACC['where']['update'] → sql|null, $ACC['fields']['update'] → маски|null
		#[\ReturnTypeWillChange]
		public function offsetGet($key)
		{
			return $this->result[$key] ?? (in_array($key, self::OPS, true) ? false : null);
		}
		#[\ReturnTypeWillChange]
		public function offsetExists($key)      { return $this->offsetGet($key) !== null; }
		#[\ReturnTypeWillChange]
		public function offsetSet($key, $value) { }
		#[\ReturnTypeWillChange]
		public function offsetUnset($key)       { }


		/*
		 *
		 * Скормить одно совпавшее правило аккумулятору.
		 * Пишет в $out: ops[op]=true, where[op][]=sql-фрагменты,
		 * hard[op]=true (грант без where), fields[op]+=маски полей
		 * name: QCatalogAccess::eat
		 * @param rule — правило субъекта, out — аккумулятор
		 * @return void
		 *
		 */
		private function eat(array $rule, &$out)
		{
			//строка — безусловный слот '*', массив — по-операционно
			$whr = is_array($rule['where'] ?? null) ? $rule['where'] : ['*' => $rule['where'] ?? null];
			$fld = is_array($rule['fields'] ?? null) ? $rule['fields'] : ['*' => $rule['fields'] ?? null];

			//scoped-ключи — только '*' или операция; опечатка в конфиге = громкая ошибка
			foreach (array_keys($whr + $fld) as $scope)
				if ($scope !== '*' and !in_array($scope, self::OPS, true))
					throw new \Exception("Unknown scope '$scope' in access rules for '{$this->catalog}'");

			foreach ($this->ops($rule['ops'] ?? null) as $op)
			{
				$out['ops'][$op] = true;
				//where.* и where.<op> стекуются через AND; без where — безусловно
				$frag = implode(' AND ', array_filter([$whr['*'] ?? null, $whr[$op] ?? null]));
				$frag ? $out['where'][$op][] = "($frag)" : $out['hard'][$op] = true;
			}

			foreach (self::OPS as $op)
				if ($m = $fld[$op] ?? $fld['*'] ?? null)
					$out['fields'][$op] = array_merge($out['fields'][$op] ?? [], $this->listOf($m));
		}

		//Список операций из значения ops; '*' — все; неизвестная операция — ошибка конфига
		private function ops($value)
		{
			$ops = [];
			foreach ($this->listOf($value) as $op)
			{
				if ($op === '*') return self::OPS;
				if (!in_array($op, self::OPS, true))
					throw new \Exception("Unknown operation '$op' in access rules for '{$this->catalog}'");
				$ops[] = $op;
			}
			return $ops;
		}

		//JSON-массив '["a","b"]' или comma-строка 'a,b' → список
		private function listOf($value)
		{
			if ($value === null or $value === '') return [];
			if (is_array($value)) return $value;

			$value = trim($value);
			if ($value[0] !== '[') return array_filter(array_map('trim', explode(',', $value)), 'strlen');

			if (!is_array($list = json_decode($value, true)))
				throw new \Exception("Invalid list in access rules for '{$this->catalog}': $value");
			return $list;
		}

		private function any(array $masks, $value)
		{
			foreach ($masks as $mask) if (fnmatch($mask, $value, FNM_CASEFOLD)) return true;
			return false;
		}
	}



	# ---------------------------------------------------------------- #
	# --------------[ СОЗДАЕМ И ПОДКЛЮЧАЕМ ИНТЕРФЕЙС ]---------------- #
	# ---------------------------------------------------------------- #
	return new SimpleCatalog($this->db, $this->config);
