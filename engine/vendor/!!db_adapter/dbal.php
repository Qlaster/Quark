<?php



	/*

	//Создание таблицы
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
	
	//Создание индекса
	$orm->table('tablename')->index('indexname')->create($columns);
	$orm->table('tablename')->index_create('indexname', $columns);
	
	//Дополнительные PRIMARY KEY - по принципу PRIMARY KEY("id","from","to") TODO: стандарт не определен
	$orm->table('tablename')->pkey($columns)->create($columns); 
	
	
	//Удаление индекса
	$orm->table('tablename')->index('indexname')->drop();
	$orm->table('tablename')->index('indexname')->delete();
	$orm->table('indexname')->index_drop('indexname');
	
	
	//Запросы
	$orm->table('tablename')->insert($data);
	$orm->table('tablename')->delete();
	$orm->table('tablename')->update($data);
		
	$orm->table('tablename')->like($data)->where('i > ? and i < ?', $i1, $i2)->select('id, name, info');
	$orm->table('tablename')->like($data)->where($data)->select();
		
	*/
	
	//$orm->table('tablename')->like($data)->where('i > ? and i < ?', $i1, $i2)->select('id, name, info');

	class ORM_PDO
	{
		
		private $qinfo;
		private $PDO_INTERFACE;
		public $rowCount = 0;
		public $PDO;
		public $transaction = false;
		
		function __construct($PDO_interface)
		{
			//Если переданный клас не является PDO, то показываем ошибку
			if ( ! ($PDO_interface instanceOf PDO) )
			{
				//...выбрасываем предупреждение
				trigger_error ( "Переданный интерфейс не является объектом PDO." , E_USER_WARNING ); 
				return false;
			}
			
			//Устанавливаем интерфейс к базе данных
			$this->PDO_INTERFACE = &$PDO_interface;
			//Переводим в режим предупреждений
			//~ $this->PDO_INTERFACE->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );  
			$this->PDO_INTERFACE->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			//PDO будет возвращать только ассоциативные массивы
			$this->PDO_INTERFACE->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
			//NULL преобразовывать в пустые строки.
			//~ $this->PDO_INTERFACE->setAttribute(PDO::ATTR_ORACLE_NULLS, PDO::NULL_TO_STRING);

			//Обнуляем параметры запроса
			$this->Reset();
			$this->PDO = &$this->PDO_INTERFACE;
		}
		
		
		function reset()
		{
			//Обнуляем параметры запроса
			$this->qinfo['table'] = '';		
			$this->qinfo['columns'] = '';		
			$this->qinfo['where'] = array();
			$this->qinfo['order'] = '';
			$this->qinfo['limit'] = '';    	
			$this->qinfo['join']  = array();
			$this->qinfo['concat'] = 'and';
		}		
		
		function concat($concat)
		{
			$this->qinfo['concat'] = $concat;
		}
		
		function beginTransaction()
		{
			$this->PDO_INTERFACE->beginTransaction();
			$this->transaction = true;
			return $this;
		}
		
		
		function commit()
		{
			$this->transaction = false;
			return $this->PDO_INTERFACE->Commit();
		}
		
		//Возвращает количество записей, который затронул последний запрос
		function rowCount()
		{
			
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
			
			//Формируем запрос
			$stmt = $this->PDO_INTERFACE->prepare(		
				"CREATE TABLE IF NOT EXISTS \"$table\"
					(
						$columns
					);
				");
			
			//Выполняем
			if ($stmt) $stmt->execute();
			
			 
			return $this;
		}
		
		
		/*
		 * 
		 * name: Списо кполей таблицы
		 * @param 
		 * @return
		 * 
		 */	
		
		function columns()
		{
			//Выдергиваем имя таблицы
			$table = $this->qinfo['table'];		
		
			if ($this->PDO_INTERFACE->getAttribute(PDO::ATTR_DRIVER_NAME) == "sqlite")
			{
				//~ $stmt = $this->PDO_INTERFACE->prepare("SHOW COLUMNS FROM `$table`;");						
				//~ $stmt->execute();
				
				$stmt = $this->PDO_INTERFACE->prepare("PRAGMA table_info($table);");
				//~ $stmt = $this->PDO_INTERFACE->prepare("PRAGMA table_info($table);");						
				//~ $stmt = $this->PDO_INTERFACE->prepare("SELECT * FROM sqlite_master WHERE type = 'table';");						
				$stmt->execute();
				$columns = $stmt->fetchAll();
				foreach ($columns as $recordCol)
					$result[$recordCol['name']] = $recordCol;
								
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
			$this->qinfo['table'] = (string) $tablename;
			//Нужно ЭКРАНИРОВАНИЕ!!
			
			return $this;
		}
		
		
		function drop()
		{
			$table = $this->qinfo['table'];
			$table = str_replace('"','\"',$table);	//Экранируем ковычки
			$stmt = $this->PDO_INTERFACE->prepare("DROP TABLE IF EXISTS \"$table\";");
			$stmt->execute();
		}
		
		/*
		 * 
		 * name: Псевдоним для table
		 * @param Имя таблицы
		 * @return
		 * 
		 */
		function from($tablename = '')
		{
			return $this->table($tablename = '');
		}
		
		
		function tables()
		{
			$sql = 'SHOW TABLES';
			$sql = 'SELECT name FROM sqlite_master WHERE type = "table"';
			
			
			//~ if($this->PDO_INTERFACE->is_connected)
			{
				$query = $this->PDO_INTERFACE->query($sql);
				return $query->fetchAll(PDO::FETCH_COLUMN);
			}
			return FALSE;
    
    
			$stmt = $this->PDO_INTERFACE->prepare("show tables from crawler;");	
			$stmt->execute();
			
			return $stmt->fetchAll();
			
			//Формируем запрос
			//~ $stmt = $this->PDO_INTERFACE->prepare("SELECT * FROM INFORMATION_SCHEMA.TABLES;");		
			$stmt = $this->PDO_INTERFACE->prepare("SHOW TABLES;");		
				
			//Выполняем его
			$stmt->execute();
			
			//Возвращем результат выборки
			return $stmt->fetchAll();
		}
		
		function SQL($SQL, $params=null)
		{						
			//Формируем запрос
			$stmt = $this->PDO_INTERFACE->prepare($SQL);

			//Выполняем
			if ($stmt) $stmt->execute($params);
			 
			return $stmt->fetchAll();;				
		}
		
		function insert($record)
		{
			//Выдергиваем имя таблицы
			$table = $this->qinfo['table'];			
			if ((!is_array($record)) or (count($record) == 0)) return $this;

			//Вытягиваем колонки
			$columns = implode('","', array_keys($record));
			
			//Приклеиваем двоеточие ко всем ключам массива
			foreach ($record as $key => &$value) $values[] = ":$key";						
			$values  = implode(', ', $values);
			
			//Формируем запрос	
			($this->PDO_INTERFACE->getAttribute(PDO::ATTR_DRIVER_NAME) != "sqlite") ? $returning = "RETURNING *" : $returning = "";
			
			//Формируем запрос	
			$stmt = $this->PDO_INTERFACE->prepare("INSERT INTO \"$table\" (\"$columns\") values ($values) $returning;");			
			
			//Указываем значения
			foreach ($record as $key => &$val) $stmt->bindParam(":$key", $val);			
			
			//отправляем запрос на выполнение
			$stmt->execute();					
			
			//Обновляем количество записей, затронутых запросом
			$this->rowCount		= $stmt->rowCount();	
			$this->lastRecord   = $stmt->fetchAll();
			$this->lastInsertId = end($this->lastRecord)['id'];	
			
			if (!$this->lastInsertId) $this->lastInsertId = $this->PDO_INTERFACE->lastInsertId();
          
			//Очистим условия для дальнейших запросов
			$this->Reset();
			
			return $this;
		}
		
		
		
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
              
				//Прописываем колонки
				$columns[] = "\"$key\" = ?";
				if ($value == 'NULL') $value = null;
				//~ $values[":$key"] = "$key";
					
			}	
			$columns = implode(', ', $columns);
			//~ $values  = implode(', ', $values);

			//~ print_r($columns); die;
			
			//Выбираем условие
			$where 	= $this->WhereComposition($this->qinfo['concat']);
			$params = $this->WhereParams();
			
			//~ print_r($values+$params); die;
			
			//~ echo "UPDATE '$table' SET $columns WHERE ($where);"; die;
			
			if ($where)
			{
				//Формируем запрос	
				$stmt = $this->PDO_INTERFACE->prepare("UPDATE \"$table\" SET $columns WHERE ($where);");	
				
				//Указываем значения
				foreach ($record as $key => &$val) $stmt->bindParam(":$key", $val);	
				
				//отправляем запрос на выполнение (подставляем параметры)
				$stmt->execute(array_values((array)$record+(array)$params));	
				
				//Обновляем количество записей, затронутых запросом
				$this->rowCount = $stmt->rowCount();	
			}
			
          
			//Очистим условия для дальнейших запросов
			$this->Reset();
          
			return $this;
		}
		
		
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
			
			//~ exit("REPLACE INTO \"$table\" (\"$columns\") values ($values);");
			//Формируем запрос	
			$stmt = $this->PDO_INTERFACE->prepare("REPLACE INTO \"$table\" (\"$columns\") values ($values);");			
			 
			//Указываем значения
			foreach ($record as $key => &$val) $stmt->bindParam(":$key", $val);			
			
			//отправляем запрос на выполнение
			$stmt->execute();					
			
			//Обновляем количество записей, затронутых запросом
			$this->rowCount		= $stmt->rowCount();	
			$this->lastRecord   = $stmt->fetchAll();
			$this->lastInsertId = end($this->lastRecord)['id'];	
			
			if (!$this->lastInsertId) $this->lastInsertId = $this->PDO_INTERFACE->lastInsertId();
          
			//Очистим условия для дальнейших запросов
			$this->Reset();
			
			return $this;
		}
		
		
		/*
		 * 
		 * name: Удаление
		 * @param 
		 * @return
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
				$stmt = $this->PDO_INTERFACE->prepare("DELETE FROM \"$table\" WHERE ($where);");		
				//отправляем запрос на выполнение (подставляем параметры)
				$stmt->execute($params);	
				
				//Обновляем количество записей, затронутых запросом
				$this->rowCount = $stmt->rowCount();	
			}
          
			//Очистим условия для дальнейших запросов
			$this->Reset();
						
			return $this;
		}
		
		
		/*
		 * 
		 * name: Выборка по условию. Вызывается $orm->table('tablename')->where('partners WHERE email=? AND pass=?', $a, $b)->select();
		 * @param 
		 * @return
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
			
			//~ $columns = '`'. (string) implode('`, `', $columns).'`';			
			//~ echo "SELECT $columns FROM '$table' WHERE ($where);"; die;			
			//~ echo "SELECT $columns FROM '$table';";
			
			//Сортировка
			//if ($order) $order = ' ORDER BY "'.str_replace('"', '\"', $order).'"';
			if ($order) $order = " ORDER BY $order";
			
			//Группировка
			//if ($group) $group = ' GROUP BY "'.str_replace('"', '\"', $group).'"';
			if ($group) $group = " GROUP BY $group";
			
			//Табличка
			//if ($table) $table = "FROM \"$table\"";
			if ($table) $table = "FROM $table";
          
			//Join
			$join = $this->joinComposition();
	
			if ($where)
				//Подготавливаем запрос
				$stmt = $this->PDO_INTERFACE->prepare("SELECT $columns $table $join WHERE ($where) $group $order $limit;");
			else
				//Подготавливаем запрос
				$stmt = $this->PDO_INTERFACE->prepare("SELECT $columns $table $join $group $order $limit;");	
			
			//~ if ($fetch == 'x') exit("SELECT $columns FROM \"$table\" WHERE ($where) $order $limit;");
			
			//Выполняем запрос
			$stmt->execute($params);
			
			//Очистим условия для дальнейших запросов
			$this->Reset();
          
			//Возвращем результат выборки
			return $stmt->fetchAll($fetch);
		}
		
		
		
		/*
		 * 
		 * name: Условия выборки. Вызывается $orm->table('tablename')->where('partners WHERE email=? AND pass=?', $a, $b)->select();
		 * @param 
		 * @return
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
					//Собираем условие запроса
					//~ $where = '(' . implode(' = ? ) and (', array_keys($args[0]) ) . ' = ? )';					
					foreach ($args[0] as $key => $value) 
					{
						//Если указан целый набор значений, то объдиним их в "in ()"
						if (is_array($value))
						{
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
					
					//Сбрасываем ключи переданного массива
					//~ $values = array_values($args[0]);
					//Собираем условие запроса
					//~ $where = '(' . implode(' = ? ) and (', array_keys($args[0]) ) . ' = ? )';					


					$this->qinfo['where']['params'] = array_merge( (array) $this->qinfo['where']['params'], (array) $values );
					//~ print_r($this->qinfo['where'][0]['sql']); die;
					//Если все пусто - выходим
					//~ if (count($values) == 0) return $this;
					
					//~ print_r($this->qinfo['where']); die;
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
					//Собираем условие запроса
					foreach ($args[0] as $key => $value) 
					{
						if ($value == 'NULL') 
						{							
							//Если передали NULL
							$this->qinfo['where']['sql'][] = "$key IS NULL";
							//Этот параметр больше не будет учавствовать в запросе
							unset($args[0][$key]);
						}
						else
						{						
							$this->qinfo['where']['sql'][] = $key . ' LIKE ?';
						}
					}
					
					//Сбрасываем ключи переданного массива
					$values = array_values($args[0]);
					//Добавим %% к строке, что бы отрабатывал like
					foreach	($values as &$_par) $_par = "%$_par%";
										
					$this->qinfo['where']['params'] = array_merge( (array) $this->qinfo['where']['params'], (array) $values );
					
				}
				
			}
			return $this;
		}



		public function OrderBy($order)
		{
			$this->qinfo['order'] = $order;
			return $this;
		}
		
		public function groupBy($group)
		{
			$this->qinfo['group'] = $group;
			return $this;
		}
		
		
		public function limit($limit, $offset=null)
		{
			if (is_numeric($limit))		$this->qinfo['limit'] .= " LIMIT $limit";
			if (is_numeric($offset))	$this->qinfo['limit'] .= " OFFSET $offset";	          
			//$this->qinfo['limit'] = " LIMIT $limit, $offset";
			return $this;
		}
		
		
		public function join($table, $on)
		{
			//TODO:Этот хак с предикатом ON нужно поправить
			$this->qinfo['join']['sql'][] = 'JOIN "'.str_replace('"', '\"', $table).'" ON '.$on;			
			$this->qinfo['join']['on'][]	= $on;
			return $this;
		}
		
		public function joinLeft($table, $on)
		{
			//TODO:Этот хак с предикатом ON нужно поправить
			$this->qinfo['join']['sql'][] = 'LEFT JOIN "'.str_replace('"', '\"', $table).'" ON '.$on;
			$this->qinfo['join']['on'][]	= $on;
			return $this;
		}
		
		private function joinComposition()
		{
			if (count($this->qinfo['join']['sql']) == 0) return '';			
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
		
		public function whereParams()
		{
			return $this->qinfo['where']['params'];
		} 



		
		/*
		private function WhereComposition()
		{
			if (count($this->qinfo['where']) == 0) return '';
			return implode(') and (', (array) $this->qinfo['where'][0]['sql']);
		}
		
		private function WhereParams()
		{
			return $this->qinfo['where'][0]['params'];
		}
		*/
	}
