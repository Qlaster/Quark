<?php
	namespace QyberTech\ORM;

	# ---------------------------------------------------------------- #
	#                      Работа с таблицами                          #
	# ---------------------------------------------------------------- #
	/*
		/Создание таблицы
		$orm->table('tablename')->create($columns);

		//переименование таблицы
		$orm->table('tablename')->rename('newname');

		//Удаление таблицы
		$orm->table('tablename')->drop();
		$orm->table('tablename')->delete();

		//Спсиок полей таблицы
		$orm->table('tablename')->columns();

		//Списиок таблиц
		$orm->tables();
	*/

	# ---------------------------------------------------------------- #
	#                            Индексы                               #
	# ---------------------------------------------------------------- #
	/*
		//Создание индекса
		$orm->table('tablename')->index('indexname')->create($columns);
		$orm->table('tablename')->index_create('indexname', $columns);

		//Дополнительные PRIMARY KEY - по принципу PRIMARY KEY("id","from","to") TODO: стандарт не определен
		$orm->table('tablename')->pkey($columns)->create($columns);

		//Удаление индекса
		$orm->table('tablename')->index('indexname')->drop();
		$orm->table('tablename')->index('indexname')->delete();
		$orm->table('indexname')->index_drop('indexname');
	*/

	# ---------------------------------------------------------------- #
	#            Вызов хранимых функций и прямой SQL                   #
	# ---------------------------------------------------------------- #
	/*
		//Вызов хранимой функции
		$orm->select("demofunction(3, 'John')");
		$orm->limit(1,5)->select(" demofunction(3, 'John') ");

		//Вызов SQL
		$orm->SQL("select * from demotable where id=? and name?", ['3', 'John']);
	*/


	# ---------------------------------------------------------------- #
	#                       Типовые запросы                            #
	# ---------------------------------------------------------------- #
	/*
		//Вставить запись
		$orm->table('tablename')->insert($data);

		//Удалить запись с id=6
		$orm->table('tablename')->where(['id'=>6])->delete();

		//Обновление записи с id=2
		$orm->table('tablename')->where(['id'=>2])->update($data);

		//Выборка с использованием like
		$orm->table('tablename')->like($data)->where('i > ? and i < ?', $i1, $i2)->select('id, name, info');
		$orm->table('tablename')->like($data)->where($data)->select();
	*/

	class QORM
	{

		private $qinfo;
		private $PDO_INTERFACE;
		public  $PDO;
		//Состояние транзакции
		public  $transaction = false;
		//Слепок последнего SQL запроса
		public  $lastQuery = '';

		function __construct($PDO_interface)
		{
			//Если переданный клас не является PDO, то показываем ошибку
			if ( ! ($PDO_interface instanceOf \PDO) )
			{
				//...выбрасываем предупреждение
				trigger_error ( "Переданный интерфейс не является объектом PDO." , E_USER_WARNING );
				return false;
			}

			//Устанавливаем интерфейс к базе данных
			$this->PDO_INTERFACE = &$PDO_interface;
			//Переводим в режим предупреждений
			//~ $this->PDO_INTERFACE->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );
			$this->PDO_INTERFACE->setAttribute( \PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
			//PDO будет возвращать только ассоциативные массивы
			$this->PDO_INTERFACE->setAttribute( \PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
			//NULL преобразовывать в пустые строки.
			//~ $this->PDO_INTERFACE->setAttribute(PDO::ATTR_ORACLE_NULLS, PDO::NULL_TO_STRING);

			//Обнуляем параметры запроса
			$this->Reset();
			$this->PDO = &$this->PDO_INTERFACE;
		}

		/*
		 *
		 * name: Сбросим состояние
		 * @param
		 * @return ORM instance
		 *
		 */
		function reset()
		{
			//Обнуляем параметры запроса
			$this->qinfo['table']           = '';
			$this->qinfo['columns']         = '';
			$this->qinfo['where']           = [];
			$this->qinfo['where']['sql']    = [];
			$this->qinfo['where']['params'] = [];
			$this->qinfo['order']           = '';
			$this->qinfo['limit']           = '';
			$this->qinfo['join']            = [];
			$this->qinfo['concat']          = 'and';
			$this->qinfo['pkey']            = [];
			$this->qinfo['group']           = '';
		}

		/*
		 *
		 * name: Устанавливает способ конкатенации запросов
		 * @param
		 * @return ORM instance
		 *
		 */
		function concat($concat)
		{
			$this->qinfo['concat'] = $concat;
			return $this;
		}


		/*
		 *
		 * name: Определяет первичные ключи при создании запроса, например table('tablename')->pkey($columns)->create($columns) или PRIMARY KEY("id","from","to")
		 * @param string items
		 * @return ORM instance
		 *
		 */
		function pkey()
		{
			//Получаем аргументы функции
			$args = func_get_args();
			if (count($args) == 0) return $this;

			if ((count($args) == 1) and (is_array(current($args))))
			{
				$this->qinfo['pkey'] = current($args);
				return $this;
			}

			if ($args)
			{
				$this->qinfo['pkey']  = $args;
			}

			return $this;
		}

		/*
		 *
		 * name: Начало транзакции
		 * @param
		 * @return ORM instance
		 *
		 */
		function beginTransaction()
		{
			$this->PDO_INTERFACE->beginTransaction();
			$this->transaction = true;
			return $this;
		}

		/*
		 *
		 * name: Проверка на открытие транзакции
		 * @param
		 * @return (bool)
		 *
		 */
		function inTransaction()
		{
			return $this->PDO_INTERFACE->inTransaction();
		}

		/*
		 *
		 * name: Откат транзакции
		 * @param
		 * @return (bool)
		 *
		 */
		function rollBack()
		{
			return $this->PDO_INTERFACE->rollBack();
		}

		/*
		 *
		 * name: Фиксация транзакции
		 * @param
		 * @return ORM instance
		 *
		 */
		function commit()
		{
			$this->transaction = false;
			return $this->PDO_INTERFACE->Commit();
		}


		/*
		 *
		 * name: Создание таблицы.
		 * Пример "текучего" интерфейса: $orm->table('tablename')->create($columns);
		 * @param Массив столбцов.
		 * @return
		 *
		 */
		function create($columns = array())
		{
			//Если не указали имя таблицы
			if (! $this->qinfo['table']) return false;

			//не указали столбцы
			if (count($columns) == 0) return false;

			//Выдергиваем имя таблицы
			$table = $this->qinfo['table'];


			//Проводим некоторые предварительные операции с данными
			foreach ($columns as $key => &$value)
			{
				//Попытка экранировать символы
				//~ $value = str_replace('`', '', $value);
				//~ $value = str_replace('\\', '', $value);
				//~ $value = "`$value`";

				$value = "\"$key\" $value";
			}


			//Собираем поля будущей таблички в строку
			//~ $columns = implode("`,`", $columns);
			$columns = implode(", ", $columns);

			//Если есть ключевые поля - внесем их в конструкцию
			if ($this->qinfo['pkey'])
			{
				$primaryKey = implode('", "', $this->qinfo['pkey']);
				$primaryKey = ", PRIMARY KEY(\"$primaryKey\")";
			}
			$primaryKey = $primaryKey ?? '';

			//Формируем запрос
			$stmt = $this->PDO_INTERFACE->prepare(
				"CREATE TABLE IF NOT EXISTS $table
					(
						$columns
						$primaryKey
					);
				");

			//Выполняем
			if ($stmt) $stmt->execute();
			return $this;
		}


		/*
		 *
		 * name: Список полей таблицы
		 * @param
		 * @return
		 *
		 */
		function columns()
		{
			//Выдергиваем имя таблицы
			$table = $this->qinfo['table'];

			if ($this->PDO_INTERFACE->getAttribute(\PDO::ATTR_DRIVER_NAME) == "sqlite")
			{
				//~ $stmt = $this->PDO_INTERFACE->prepare("SHOW COLUMNS FROM `$table`;");
				//~ $stmt->execute();

				$stmt = $this->PDO_INTERFACE->prepare("PRAGMA table_info($table);");
				//~ $stmt = $this->PDO_INTERFACE->prepare("SELECT * FROM sqlite_master WHERE type = 'table';");
				$stmt->execute();
				$columns = $stmt->fetchAll();
				foreach ($columns as $recordCol)
					$result[$recordCol['name']] = $recordCol;

				//Возвращем результат выборки
				return $result;
			}

			if ($this->PDO_INTERFACE->getAttribute(\PDO::ATTR_DRIVER_NAME) == "pgsql")
			{
				$table = trim($table, '"');
				$stmt = $this->PDO_INTERFACE->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '$table';");
				$stmt->execute();
				$columns = $stmt->fetchAll();

				foreach ($columns as $recordCol)
					$result[$recordCol['column_name']] = $recordCol['column_name'];

				//Возвращем результат выборки
				return $result;

			}
		}


		/*
		 *
		 * name: Указание таблицы
		 * @param Имя таблицы
		 * @return
		 *
		 */
		function table($tablename = '')
		{
			$this->Reset();
			//~ $this->qinfo['table'] = (string) $tablename;
			$this->qinfo['table'] = '"'.str_replace('"', '\"',  (string) $tablename).'"';
			return $this;
		}

		/*
		 *
		 * name: from без экранирования
		 * @param Имя таблицы
		 * @return
		 *
		 */
		function from($tablename = '')
		{
			$this->qinfo['table'] = $tablename;
			return $this;
		}

		/*
		 *
		 * name: Фиксация транзакции
		 * @param
		 * @return stmt result
		 *
		 */
		function drop()
		{
			//~ $table = str_replace('"', '\"', $this->qinfo['table']);	//Экранируем ковычки
			$table = $this->qinfo['table'];
			$stmt = $this->PDO_INTERFACE->prepare("DROP TABLE IF EXISTS $table;");
			return $stmt->execute();
		}


		/*
		 *
		 * name: Список таблиц базы
		 * @param
		 * @return (array) tablelist
		 *
		 */
		function tables()
		{
			if ($this->PDO_INTERFACE->getAttribute(\PDO::ATTR_DRIVER_NAME) == "sqlite") $sql = 'SELECT name FROM sqlite_master WHERE type = "table";';
			if ($this->PDO_INTERFACE->getAttribute(\PDO::ATTR_DRIVER_NAME) == "mysql" ) $sql = 'SHOW TABLES;';

			if ($sql)
			{
				$query = $this->PDO_INTERFACE->query($sql);
				return $query->fetchAll(\PDO::FETCH_COLUMN);
			}

			return FALSE;
		}

		/*
		 *
		 * name: Прямой SQL запрос
		 * @param
		 * @return (array) result
		 *
		 */
		function SQL($SQL, $params=null)
		{
			//Запишем последний запрос
			$this->lastQuery = $SQL;

			//Формируем запрос
			$stmt = $this->PDO_INTERFACE->prepare($SQL);

			//Выполняем
			if ($stmt)
			{
				$stmt->execute($params);
				//Закинем последнее состояние в интерфейс состояния PDO
				$this->PDO->stmt = $stmt;
			}

			return $stmt->fetchAll();;
		}

		/*
		 *
		 * name: Вставить запись
		 * @param (array) column
		 * @return ORM instance
		 *
		 */
		function insert($record)
		{
			//Выдергиваем имя таблицы
			$table = $this->qinfo['table'];
			if ((!is_array($record)) or (count($record) == 0)) return $this;

			//Вытягиваем колонки
			$columns = implode('","', array_keys($record));

			//Приклеиваем указатели переменным
			$values = str_pad('', count($record)*2-1, '?,');

			//Формируем запрос
			($this->PDO_INTERFACE->getAttribute(\PDO::ATTR_DRIVER_NAME) != "sqlite") ? $returning = "RETURNING *" : $returning = "";

			//Формируем запрос
			$this->lastQuery = "INSERT INTO $table (\"$columns\") values ($values) $returning;";

			//Отдаем запрос а разбор
			$stmt = $this->PDO_INTERFACE->prepare($this->lastQuery);

			//отправляем запрос на выполнение
			$stmt->execute(array_values($record));

			//Закинем последнее состояние в интерфейс состояния PDO
			$this->PDO->stmt = $stmt;

			//Обновляем количество записей, затронутых запросом
			//~ $this->rowCount		= $stmt->rowCount();
			//~ $this->lastRecord   = $stmt->fetchAll();
			//~ $this->lastInsertId = end($this->lastRecord)['id'];

			//~ if (!$this->lastInsertId) $this->lastInsertId = $this->PDO_INTERFACE->lastInsertId();

			//Очистим условия для дальнейших запросов
			$this->Reset();

			return $this;
		}


		/*
		 *
		 * name: Обновить запись
		 * @param (array) column
		 * @return ORM instance
		 *
		 */
		function update($record)
		{
			//Выдергиваем имя таблицы
			$table = $this->qinfo['table'];

			//Если нам подсунули в качестве параметра неизвестно что
			if ((!is_array($record)) or (count($record) == 0)) return $this;


			//Вытягиваем колонки
			//~ $columns = implode('`,`', array_keys($record));

			//Приклеиваем двоеточие ко всем ключам массива
			foreach ($record as $key => &$value)
			{
				if (is_array($value))
				{
					foreach ($value as $_arrkey => &$_arrvalue)
					{
						if (is_string($_arrvalue) and ($_arrvalue != 'NULL'))  $_arrvalue = "'".str_replace("'", "''", $_arrvalue)."'";
						$arrelements[] = $_arrvalue;
					}
					$arrelements = implode(',', $arrelements);
					$columns[] = "\"$key\" = '{{$arrelements}}'";
					unset($record[$key]);
				}
				else
				{
					//Прописываем колонки
					$columns[] = "\"$key\" = ?";
					if ($value == 'NULL') $value = null;
				}
			}
			$columns = implode(', ', $columns);

			//Выбираем условие
			$where 	= $this->WhereComposition($this->qinfo['concat']);
			$params = $this->WhereParams();

			if ($where)
			{
				//Формируем запрос возврата данных
				$returning = ($this->PDO_INTERFACE->getAttribute(\PDO::ATTR_DRIVER_NAME) != "sqlite") ? "RETURNING *" : "; SELECT last_insert_rowid();";
				$this->lastQuery = "UPDATE $table SET $columns WHERE ($where) $returning;";

				//Формируем запрос
				$stmt = $this->PDO_INTERFACE->prepare($this->lastQuery);

				//Указываем значения
				//~ foreach ($record as $key => &$val) $stmt->bindParam(":$key", $val);

				//отправляем запрос на выполнение (подставляем параметры)
				$stmt->execute(array_values((array)$record+(array)$params));

				//Обновляем количество записей, затронутых запросом
				//~ $this->rowCount = $stmt->rowCount();

				//Закинем последнее состояние в интерфейс состояния PDO
				$this->PDO->stmt = $stmt;
			}

			//Очистим условия для дальнейших запросов
			$this->Reset();

			return $this;
		}

		/*
		 *
		 * name: Замена записей
		 * @param (array) column
		 * @return ORM instance
		 *
		 */
		function replace($record)
		{
			//Выдергиваем имя таблицы
			$table = $this->qinfo['table'];

			if ((!is_array($record)) or (count($record) == 0)) return $this;

			//Вытягиваем колонки
			$columns = implode('","', array_keys($record));

			//Приклеиваем двоеточие ко всем ключам массива
			foreach ($record as $key => &$value) $values[] = ":$key";
			$values  = implode(', ', $values);

			//Формируем запрос возврата данных
			$this->lastQuery = "REPLACE INTO $table (\"$columns\") values ($values);";

			//Формируем запрос
			$stmt = $this->PDO_INTERFACE->prepare($this->lastQuery);

			//Указываем значения
			foreach ($record as $key => &$val) $stmt->bindParam(":$key", $val);

			//отправляем запрос на выполнение
			$result = $stmt->execute();

			//Закинем последнее состояние в интерфейс состояния PDO
			$this->PDO->stmt = $stmt;

			//Обновляем количество записей, затронутых запросом
			//~ $this->rowCount		= $stmt->rowCount();
			//~ $this->lastRecord   = $stmt->fetchAll();
			//~ $this->lastInsertId = end($this->lastRecord)['id'];

			//~ if (!$this->lastInsertId) $this->lastInsertId = $this->PDO_INTERFACE->lastInsertId();

			//Очистим условия для дальнейших запросов
			$this->Reset();

			return $this;
		}


		/*
		 *
		 * name: Удаление
		 * @param
		 * @return ORM instance
		 *
		 */
		function delete()
		{
			//Выдергиваем имя таблицы
			$table = $this->qinfo['table'];

			//Выбираем условие
			$where 	= $this->WhereComposition($this->qinfo['concat']);
			$params = $this->WhereParams();

			if ($where)
			{
				//Формируем запрос
				$stmt = $this->PDO_INTERFACE->prepare("DELETE FROM $table WHERE ($where);");
				//отправляем запрос на выполнение (подставляем параметры)
				$stmt->execute($params);

				//Обновляем количество записей, затронутых запросом
				//~ $this->rowCount = $stmt->rowCount();

				//Закинем последнее состояние в интерфейс состояния PDO
				$this->PDO->stmt = $stmt;
			}

			//Очистим условия для дальнейших запросов
			$this->Reset();

			return $this;
		}


		/*
		 *
		 * name: Выборка по условию. Вызывается $orm->table('tablename')->where('partners WHERE email=? AND pass=?', $a, $b)->select();
		 * @param (array) columns, fetch params
		 * @return ORM instance
		 *
		 */
		function select($columns = '*', $fetch = NULL)
		{
			//Выдергиваем имя таблицы
			$table = $this->qinfo['table'];
			$order = $this->qinfo['order'];
			$group = $this->qinfo['group'];
			$limit = $this->qinfo['limit'];

			//Выбираем условие
			$where 	= $this->WhereComposition($this->qinfo['concat']); //В concat хранится способ сцепления выражений. По умолчанию and
			$params = $this->WhereParams();

			//Если вдруг NULL прилетел или пустая строка, то это нарушит запрос. Подставим по умолчангию
			if	(!$columns) $columns = '*';

			//Сортировка
			//if ($order) $order = ' ORDER BY "'.str_replace('"', '\"', $order).'"';
			if ($order) $order = " ORDER BY $order";

			//Группировка
			//if ($group) $group = ' GROUP BY "'.str_replace('"', '\"', $group).'"';
			if ($group) $group = " GROUP BY $group";

			//Табличка
			if ($table) $table = "FROM $table";
			//~ if ($table) $table = "FROM $table";

			//Join
			$join = $this->joinComposition();

			//~ if ($where)
				//~ //Подготавливаем запрос
				//~ $stmt = $this->PDO_INTERFACE->prepare("SELECT $columns $table $join WHERE ($where) $group $order $limit;");
			//~ else
				//~ //Подготавливаем запрос
				//~ $stmt = $this->PDO_INTERFACE->prepare("SELECT $columns $table $join $group $order $limit;");

			$this->lastQuery = $where ? "SELECT $columns $table $join WHERE ($where) $group $order $limit;" : "SELECT $columns $table $join $group $order $limit;";
			//$this->lastQuery = "SELECT $columns $table $join WHERE ($where) $group $order $limit;";

			//Подготавливаем запрос
			$stmt = $this->PDO_INTERFACE->prepare($this->lastQuery);

			//Выполняем запрос
			$stmt->execute($params);

			//Очистим условия для дальнейших запросов
			$this->Reset();

			//Возвращем результат выборки
			return @$stmt->fetchAll($fetch);
		}

		/*
		 *
		 * name: Условия выборки. Вызывается $orm->table('tablename')->where('partners WHERE email=? AND pass=?', $a, $b)->select();
		 * @param ...
		 * @return ORM instance
		 *
		 */
		function where()
		{
			//Получаем аргументы функции
			$args = func_get_args();

			//Если нам передали 1 параметр
			if ( count($args) == 1 )
			{
				//Если нам передали 1 параметр и он пустой - то попросили очистить where
				if ($args[0] === '')
				{
					$this->qinfo['where']['sql'] = array();
					$this->qinfo['where']['params']	= array();
					return $this;
				}

				//Если нам передали 1 параметр и это строка
				if ( ($args[0]) and (is_string($args[0])) )
				{
					$this->qinfo['where']['sql'][] = $args[0];
					//~ $this->qinfo['where']['params']	= array();
					return $this;
				}

				//Если нам передали 1 параметр и это массив
				if ( ($args[0]) and (is_array($args[0])) )
				{
					$values = [];
					//Собираем условие запроса
					foreach ($args[0] as $key => $value)
					{
						//Если указан целый набор значений, то объдиним их в "in ()"
						if (is_array($value))
						{
							if (!$value) continue;
							//Рассчитаем и сгенерируем нужное количество вопросов в запросе
							$inQuests = trim(str_repeat("?,", count($value)), ',');
							$this->qinfo['where']['sql'][] = "\"$key\" in ($inQuests)";
							$values = array_merge((array)$values, $value);
						}
						else if (($value == 'NULL') or ($value === NULL))
						{
							//Если передали NULL
							$this->qinfo['where']['sql'][] = "\"$key\" IS NULL";
							//Этот параметр больше не будет учавствовать в запросе
							//~ unset($args[0][$key]);
						}
						else
						{
							$this->qinfo['where']['sql'][] = "\"$key\" = ?";
							$values[] = $value;
						}
					}

					$this->qinfo['where']['params'] = array_merge( (array) $this->qinfo['where']['params'], (array) $values );
					//~ print_r($this->qinfo['where'][0]['sql']); die;
					//Если все пусто - выходим
					//~ if (count($values) == 0) return $this;

					return $this;
				}

			}

			if (count($args) <= 1) return $this;

			//Цепляем запрос
			$where = $args[0];
			unset($args[0]);

			//Если второй переданный параметр - массив, то берем данные из него
			if (is_array($args[1]))
			{
				//Сбрасываем ключи переданного массива
				$values = array_values($args[1]);
				//Если все пусто - выходим
				if (count($values) == 0) return $this;
			}
			elseif (is_string($args[1]) or is_int($args[1]))  	//Если второй параметр - строка, то обрабатываем как строки
			{
				$values = array_values($args);
			}

			$this->qinfo['where']['sql'][]		= $where;
			//~ $this->qinfo['where']['params'][]	= $values;
			$this->qinfo['where']['params']		= array_merge( (array) $this->qinfo['where']['params'], (array) $values );

			return $this;
		}


		/*
		 *
		 * name: Условие выбора like
		 * @param ... like query
		 * @return ORM instance
		 *
		 */
		public function like()
		{
			//Получаем аргументы функции
			$args = func_get_args();

			//Если нам передали 1 параметр
			if ( (count($args) == 1) and ($args[0]))
			{
				//Если нам передали 1 параметр и он пустой - то попросили очистить where
				if ($args[0] === '')
				{
					$this->qinfo['where']['sql'] = array();
					$this->qinfo['where']['params']	= array();
					return $this;
				}

				//Если нам передали 1 параметр и это строка, то мы осуществим поиск ее во всех полях данной таблички
				if ($args[0] and is_string($args[0]))
				{
					$likestr = '%'.$args[0].'%';
					//Пройдемся по всем полям таблички и соберем запрос
					//TODO: Ужасно не оптимальная история. Есть идея собрать все поля таблицы в одну строку и выполнить запрос по ней
					foreach ((array) $this->columns() as $key => $name) $request[$key] = "CAST(\"$key\" AS TEXT) iLIKE ?";

					$this->qinfo['where']['sql'][]  = implode(' OR ', (array) $request);
					$this->qinfo['where']['params'] = array_merge( (array) $this->qinfo['where']['params'], (array) array_fill(0, count((array)$request), $likestr) );
					return $this;
				}

				//Если нам передали 1 параметр и это массив
				if ( ($args[0]) and (is_array($args[0])) )
				{
					//Собираем условие запроса
					foreach ($args[0] as $key => $value)
					{
						//Если указан целый набор значений, то объдиним их в "in ()"
						if (is_array($value))
						{
							//Рассчитаем и сгенерируем нужное количество вопросов в запросе
							$this->qinfo['where']['sql'][] = rtrim(str_repeat("CAST(\"$key\" AS TEXT) LIKE ? OR ", count($value)), ' OR ');
							$values = array_merge((array)$values, $value);
						}
						elseif (($value === 'NULL') or ($value === NULL))
						{
							//Если передали NULL
							$this->qinfo['where']['sql'][] = "\"$key\" IS NULL";
						}
						else
						{
							$this->qinfo['where']['sql'][] = "CAST(\"$key\" AS TEXT) LIKE ?";
							$values[] = $value;
						}
					}

					//Добавим %% к сроке, что бы отрабатывал like
					if (isset($values))
					{
						foreach	($values as &$_par) $_par = "%$_par%";
						$this->qinfo['where']['params'] = array_merge( (array) $this->qinfo['where']['params'], (array) $values );
					}
				}
			}
			return $this;
		}

 		/*
		 *
		 * name: Сортировка через order by. Пример: OrderBy("column DESC") или OrderBy(['column'=>'DESC'])
		 * @param (array) or (string) params
		 * @return ORM instance
		 *
		 */
		public function OrderBy($order)
		{
			if (is_string($order))
				$this->qinfo['order'] = $order;

			if (is_array($order))
			{
				foreach	($order as $column => &$direction)
				{
					$direction	= strtoupper($direction);
					$direction	= current(array_intersect((array)$direction, ['ASC', 'DESC']));
					if (! $direction) continue;
					$column 	= str_replace("'", "\'", $column);
					$query[] = "$column $direction";
				}
				$this->qinfo['order'] = implode(',', (array) $query);
			}
			return $this;
		}

		/*
		 *
		 * name: Группировка через group by
		 * @param (array) group
		 * @return ORM instance
		 *
		 */
		public function groupBy($group)
		{
			$this->qinfo['group'] = $group;
			return $this;
		}

		/*
		 *
		 * name: Оконные ограничения
		 * @param (int) limit, (int) offset
		 * @return ORM instance
		 *
		 */
		public function limit($limit, $offset=null)
		{
			if (is_numeric($limit))		$this->qinfo['limit'] .= " LIMIT $limit";
			if (is_numeric($offset))	$this->qinfo['limit'] .= " OFFSET $offset";
			//$this->qinfo['limit'] = " LIMIT $limit, $offset";
			return $this;
		}

		/*
		 *
		 * name: join
		 * @param (string) table, (string) on
		 * @return ORM instance
		 *
		 */
		public function join($table, $on)
		{
			//TODO:Этот хак с предикатом ON нужно поправить
			$this->qinfo['join']['sql'][] = 'JOIN "'.str_replace('"', '\"', $table).'" ON '.$on;
			$this->qinfo['join']['on'][]	= $on;
			return $this;
		}

		/*
		 *
		 * name: joinLeft
		 * @param (string) table, (string) on
		 * @return ORM instance
		 *
		 */
		public function joinLeft($table, $on)
		{
			//TODO:Этот хак с предикатом ON нужно поправить
			$this->qinfo['join']['sql'][] = 'LEFT JOIN "'.str_replace('"', '\"', $table).'" ON '.$on;
			$this->qinfo['join']['on'][]	= $on;
			return $this;
		}

		/*
		 *
		 * name: Сборка Join в один запрос
		 * @param
		 * @return (string)
		 *
		 */
		private function joinComposition()
		{
			if (count($this->qinfo['join']) == 0) return '';
			return implode(" ", (array) $this->qinfo['join']['sql']);
		}

		/*
		 *
		 * name: Собирает список where в строчку, которую можно применить в запросе
		 * @param
		 * @return
		 *
		 */
		public function whereComposition($method='and')
		{
			if (count($this->qinfo['where']) == 0) return '';
			return implode(") $method (", (array) $this->qinfo['where']['sql']);
		}

		/*
		 *
		 * name: Список параметров where
		 * @param
		 * @return (array)
		 *
		 */
		public function whereParams()
		{
			if (isset($this->qinfo['where']['params']))
				return $this->qinfo['where']['params'];
		}

		/*
		 *
		 * name: Возвращает количество записей, который затронул последний запрос
		 * @param
		 * @return (integer)
		 *
		 */
		function rowCount()
		{
			if ($this->PDO->stmt)
				return $this->PDO->stmt->rowCount();
		}

		/*
		 *
		 * name: Последний добавленый id
		 * @param
		 * @return (integer)
		 *
		 */
		function lastInsertId()
		{
			 return $this->PDO->lastInsertId();
		}

		/*
		 *
		 * name: Последняя добавленная запись
		 * @param
		 * @return (array)
		 *
		 */
		function lastRecord()
		{
			if ($this->PDO->stmt)
				return $this->PDO->stmt->fetchAll();
		}


		public function __get($method)
		{
			switch ($method)
			{
				case "rowCount":
					return $this->rowCount();
				case "lastInsertId":
					return $this->lastInsertId();
				case "lastRecord":
					return $this->lastRecord();
			}

			throw new \Exception("Method not found - $method");
		}

	}
