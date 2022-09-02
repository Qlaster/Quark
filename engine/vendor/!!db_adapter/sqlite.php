<?php

	//Создаем класс базы данных SQLite реализуя интерфейс Q_interface_db (это единый интерфейс взаимодействия с базой данных, наподобии PDO)
	class Q_SQLitePDO  implements Q_interface_db
	{
		public $Folder_DataBase; 	//папка, где будут лежать базы данных
		public $Print_Notice;		//Флаг, который отвечает за ввод нотаций ошибок баз данных
		
		
		//public $Extant_DataBase;	//расшинение баз данных (пока игнорируется)
		
		
		//Служебная переменная - массив. Сохраняет временные переменные с открыми базами.
		// $OPEN_DB['название_базы_данных']['DBH'] 	-  Указатели на открытые базы данных
		// $OPEN_DB['название_базы_данных']['CMT']	-  Флаг совершения коммита
		private $OPEN_DB;			
	
		//Поиграю в Капитана, и скажу, что это - конструктор класса
		function __construct($Folder_DataBase=null, $Extant_DataBase=null) 
		{
			//Если нам скинули папку, где будут храниться базы
			if ($Folder_DataBase !== null) { $this->Folder_DataBase = $Folder_DataBase; }
			//Если нам явно указали расширение баз данных, которое следует использовать
			//if ($Extant_DataBase !== null) { $this->Extant_DataBase = $Extant_DataBase; } else {  $this->Extant_DataBase = 'sqlite3'; }
		
		}
	
	
	
		//==============================================================================
		//				РАБОТА С ФАЙЛАМИ БАЗ ДАННЫХ
		//==============================================================================
		
		
		
		//Создание базы данных
		public function Create($dbname)
		{
			//Конструируем полный путь к БД
			$dbpath = $this->GeneratePathDataBase($dbname);
			
			//Если база данных уже существует - выходим
			if ( file_exists($dbpath) ) return false;
			
			try 
			{ 
				//Открываем базу данных
				$DBH = new PDO("sqlite:$dbpath"); 
				//Вернули положительные результат
				return $DBH;
			}
			catch(PDOException $e) 
			{  
				//Если ошибка открытия 
				$this->ErrorNotice($e->getMessage());
				return false;
			}
		}
	
		//Подключение к базе данных. Базу открывать не обязательно. Модель сам будет отслеживать все подключения к ней.
		public function Open($dbname, $params = array())		
		{
			//Конструируем полный путь к БД
			$dbpath = $this->GeneratePathDataBase($dbname);	

			
			//Если база данных уже открыта - просто возвращаем указатель, не имеет смысла её открывать еще раз				
			if (isset($this->OPEN_DB[$dbpath]['DBH']))
			{
				//Если файл базы есть, то можем вернуть идентификатор
				if (file_exists($dbpath)) return $this->OPEN_DB[$dbpath]['DBH'];
				//А в противном случае снимаем идентификатор, поскольку указатель есть, а базы нет. И возвращаем неудачу.
				unset($this->OPEN_DB[$dbpath]['DBH']); 
				return false;
			}
			
			//Если не нашли файл - выходим
			if (! file_exists($dbpath) ) return false;
			
			try 
			{ 
				//Открываем базу данных
				$DBH = new PDO("sqlite:$dbpath"); 
				//Запомнили сессию к базе
				$this->OPEN_DB[$dbpath]['DBH'] = $DBH;
				//Переводим PDO в режим исключений. Пояснение: Он выбрасывает исключение, что позволяет вам ловко обрабатывать ошибки и скрывать щепетильную информацию.
				$DBH->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
				//Переводим PDO в режим, когда он возвращает тослько ассоциативные массивы
				//$DBH->setFetchMode(PDO::FETCH_ASSOC);  
				
				//Вернули положительные результат
				return $DBH;
			}
			catch(PDOException $e) 
			{  
				//Если ошибка открытия 
				$this->ErrorNotice($e->getMessage());
				return false;
			}
			
			
		}
		
		//Выводит полный список зарегистрированных баз данных
		public function DBList()
		{
			$dblist = glob($this->Folder_DataBase .DIRECTORY_SEPARATOR. "*");
	
			//Устанавливает русскую локаль. Потенциально нежелательно этого делать. 
			//В идеале нужно создать самописную функцию для составления списка имен файлов в директории с базами.
			//setlocale(LC_ALL, 'ru_RU.UTF-8');
		
			foreach ($dblist  as $key => $value)  
			{
				$dblist[$key] = basename($value);
			}

			return $dblist;

		}
		
		//Удаление базы данных по имени
		public function DBDelete($dbname)
		{
			//Конструируем полный путь к БД
			$dbpath = $this->GeneratePathDataBase($dbname);

			if (file_exists($dbpath)) 
			{ 
				if (unlink($dbpath))	//Если файл удалось удалить
				{
					return true;
				}
				$this->ErrorNotice("<b>Warning:</b> Не удалось удалить базу данных $dbname. Нет доступа к файлу.\n");
				return false;
			}
		 	else //База не найдена, возвращаем ошибку
			{ 
				$this->ErrorNotice("<b>Warning:</b> База данных '$dbname' подготовленная для удаления не найдена.\n");
				return false;
			}
		}
		
		//Функция создает резервную копию базы данных и помещает её в резервную зону
		public function DBReserved($dbname)				
		{

		}
		
		//Копирует базу данных с новым именем (предварительно проверяя, существует ли такая) 	 TODO:потенциально не нужно
		public function DBCopy($oldname, $newname)			
		{
		
		}
		
		
		//==============================================================================
		//				ВЫПОЛНЕНИЕ ПРОИЗВОЛЬНЫХ ЗАПРОСОВ
		//==============================================================================


		//Запрос к базе с ответом
		public function Query($dbname, $query)
		{
		
		
			//Открываем базу данных
			$DBH = $this->Open($dbname); 
			//Если не удалось - выходим
			if (! $DBH) 
			{
				$this->ErrorNotice("<b>Warning:</b> База данных '$dbname' для выполнения запроса не найдена.");
				return false;
			}
			
			//Выполняем запрос
			try 
			{ 
				//Конструируем полный путь к БД
				$dbpath = $this->GeneratePathDataBase($dbname);
			
				//Если стоит флаг транзакции, то Query не подходит - выполняем Exec
				if ( isset($this->OPEN_DB[$dbpath]['CMT']) )
				{
					$result = $this->Exec($dbname, $query);
				}
				else	//... юначе просто выполняем запрос к базе
				{
					$result = $DBH->query($query);
				}
			}
			catch(PDOException $e) 
			{  
				//Если ошибка выполнения 
				$this->ErrorNotice($e->getMessage());
				return false;
			}

			//Если все нормально - выдаем результат запроса
			return $result;
		}	
		
		
		//Запрос к базе без ответа
		public function Exec($dbname, $query)
		{
			//Открываем базу данных
			$DBH = $this->Open($dbname); 
			//Если не удалось - выходим
			if (! $DBH) 
			{
				$this->ErrorNotice("<b>Warning:</b> База данных '$dbname' для выполнения запроса не найдена.");
				return false;
			}
			
			//Выполняем запрос
			try 
			{ 
				$result = $DBH->exec($query);
			}
			catch(PDOException $e) 
			{  
				//Если ошибка выполнения 
				$this->ErrorNotice($e->getMessage());
				return false;
			}

			//Если все нормально - выдаем результат запроса
			return $result;
	
		}
		
		//==============================================================================
		//				Транзакции
		//==============================================================================
		
		//начать транзакцию
		public function BeginTransaction($dbname, $params='')
		{
			//Берем путь к БД
			$dbpath = $this->GeneratePathDataBase($dbname);
			//Если транзакция для базы данных уже произедена
			if ( isset( $this->OPEN_DB[$dbpath]['CMT']) ) return $this->OPEN_DB[$dbpath]['CMT'];

			//Открываем базу данных для начала транзакции
			$DBH = $this->Open($dbname, $params);
			//Если открытие базы провалилось
			if ($DBH === false) return false;
			//Начинаем транзакцию
			$DBH->beginTransaction();
			//переводим флаг коммита
			$this->OPEN_DB[$dbpath]['CMT'] = '1';
			//Возвращаем указатель на открытую базу для транзакции
			//echo '!!!!!!!!!!!!!!!!'.$this->OPEN_DB[$dbpath]['DBH'].'!!!!!!!!!!!!!!!!';
			return $this->OPEN_DB[$dbpath]['CMT'];
		}	
		
		//Выполнить транзакцию
		public function Commit($dbname)
		{
			//Берем путь к БД
			$dbpath = $this->GeneratePathDataBase($dbname);
			
			if (! isset($this->OPEN_DB[$dbpath]['DBH']) ) 
			{
				$this->ErrorNotice("<br><b>Module SQLite:</b> Невозможно провести коммит для базы данных '$dbname'. База не открыта.");
				return false;
			}
			
			//Снимаем флаг транзакции
			unset ($this->OPEN_DB[$dbpath]['CMT']);
			
			//Коммитим (выполняем транзакцию)
			return $this->OPEN_DB[$dbpath]['DBH']->commit();
		}	
		
		//==============================================================================
		//				РАБОТА С ТАБЛИЦАМИ БАЗ ДАННЫХ
		//==============================================================================
		
		//Создает таблицу $tablename в базе данных $db с полями $fields (массив полей) и возвращает идентификатор на базу данных. ID создается всегда. 
		//Пример создания текстового поля: $fields['pole1'] = 'text'; 
		//Пример создания числового поля: $fields['pole2'] = 'integer';
		//так же $fields может быть строкой формата "field1 TEXT, field2 integer"		
		public function TableCreate($dbname, $tablename, $fields)
		{
			if (! is_array($fields) ) return false; //Проверяем, массив ли нам подсунули
			
			$tablename = $this->quote($tablename);
			
			//Елси $fields - это массив с полями
			if (is_array($fields))
			{
					//Прогоняем через экран поля таблицы
					foreach ($fields as $key => &$value) 
					{
						//Экранируем имена полей
						$key_buf = $this->quote($key);
				
						if ($value != '')
						{				
							//Экранируем тип полей (на всякий слечай. А вдруг =] )
							$result[$key_buf] = $this->quote($value);
						}
						else //Если тип поля не указан - тавим универсальное дефолтное - text
						{
							$result[$key_buf] = 'text';
						}
				
					}

					$query = '';
					//Составляем запрос			
					foreach ($result as $key => $value) $query = $query ."'". $key ."'". " $value, ";			
					$query = substr($query, 0, -2); 
			}
			
			
			//Если нам отправили поля строкой
			if ( is_string($fields) ) $query = $fields; 

			//Добавляем команду запроса к началу, что бы окончательно завершить запрос
			$query = "CREATE TABLE IF NOT EXISTS '$tablename' (id INTEGER PRIMARY KEY, $query);";
			
			//Выполянем запрос
			return $this->Query($dbname, $query);
		}
		
		



		//Удаление таблицы $tablename в базе $dbname c предварительной проверкой наличия
		public function TableDelete($dbname, $tablename)	
		{
			if ($tablename == "") return false;
			//Экранируем название
			$tablename = $this->quote($tablename);

			//Выполянем запрос
			return $this->Query($dbname, "DROP TABLE IF EXISTS '$tablename';");
		}
		
		
		//Список таблиц в указанной базе
		public function TableList($dbname)
		{
			//Выполянем запрос
			$query = $this->Query($dbname, "SELECT name FROM sqlite_master WHERE type='table' ORDER BY name;");
			//$query = $this->Query($dbname, "select * from sqlite_master where type = 'table';");
		
			foreach ($query as $key => $value) 
			{
				$result[$value['name']] = $value['name'];
			}
			return $result;
		}
		
		
		//Список полей в указанной таблице в базе данных  (возможно с их типами - в качестве ключей массива)
		public function FieldList($dbname, $tablename)
		{
			if ($tablename == "") return false;
			//Экранируем название
			$tablename = $this->quote($tablename);
			//составляем запрос
			$query = $this->Query($dbname, "PRAGMA table_info('$tablename');");
		
			if (!$query) return false;
			
			foreach ($query as $key => $value) 
			{
				$result[$value['name']] = $value['name'];
			}
		
			return $result;
		}
		
		//Переименовать таблицу
		public function TableRename($dbname, $old_tablename, $new_tablename)
		{
			//Экранируем названия
			$old_tablename = $this->quote($old_tablename);
			$new_tablename = $this->quote($new_tablename);
			
			return $this->Query($dbname, "ALTER TABLE '$old_tablename' RENAME TO '$new_tablename';");
		}
		
		//==============================================================================
		//				ТИПИЗИРОВАННЫЕ ЗАПРОСЫ
		//==============================================================================

		//Выборка из базы. $columns может быть как строка с перечислением через запятую, так и массив значений
		public function Select($dbname, $tablename, $columns, $where, $sort='')
		{
			if (($dbname == "") or ($tablename == "")) return false;
			
			//Если $columns массив - делаем из него строку, параллельно экранируя
			if (is_array($columns))
			{
				//Для начала экранируем это все
				foreach ($columns as $key => &$value)  $value = $this->quote($value);
				//Собираем поля в одну строку
				$columns = "'". implode ("', '" , $columns) ."'"; 
			}
			//Если columns строка - нам просто нечего не остается, как проэкранировать её
			else
			{
				$columns = $this->quote($columns);
			}
			
			//Экранируем
			$tablename 	= $this->quote($tablename);
			$sort 	= $this->quote($sort);			
			//TODO:$where - спорный вопрос на счет экранирования where. 
			//TODO:Вполне возможно, что возникнут ситуации, когда это экранирование убьет запрос. Пока оставлю эту лазейку
		
			if ($where != "") $where = "WHERE (".$where.")";	//Если условие не пустое - то оформляем.
			if ($sort  != "")	$sort  = "ORDER BY $sort ";		//Если хотят отсортировать $sort  не удалось заключить в ковычки. Потенциально опасно, хотя $sort экранирован.
		
			//echo "SELECT $columns FROM '$tablename' $where $sort;"; die;
			//Запрос к базе
			$res = $this->Query($dbname, "SELECT $columns FROM '$tablename' $where $sort;");
			
			$result = null;
			//Оформляем результат ассоциативным массивом
			while ($row = $res->fetch(PDO::FETCH_ASSOC))
			{				
				// $row - ассоциативный массив значений, ключи - названия столбцов
				$result[] = $row;
			}
			
			return $result;
		}
		
		//Удаление записи из таблицы $tablename базы $db запись по условию $where
		public function Delete($dbname, $tablename, $where)
		{
			if (($tablename == "") or ($where == "")) return false;
			
			//Экранируем
			$tablename 	= $this->quote($tablename);

			//Запрос к базе
			return $this->Exec($dbname, "DELETE FROM '$tablename' WHERE ($where);");
		}
			
			
		//обновлние записей в таблице $tablename базы $dbname по условию $where.
		//$rec может представлять собой ассоциативный массив: $rec['column1'] = value1, $rec['column2'] = value2, 
		public function Update($dbname, $tablename, $rec, $where)
		{
			if ( ($tablename == "") or (!is_array($rec)) )  return false;
			
			//Экранируем
			$tablename 	= $this->quote($tablename);

			//Для начала экранируем это все
			foreach ($rec as $key => &$value)
			{	
				//Делаем из элемента массива вот такое вот выражение column1 = 'value1'				
				$value = "'" . $this->quote($key) . "'" . " = '". $this->quote($value) ."'";
			}
			
			
			//Собираем поля в одну строку
			$set_data  = implode (", " , $rec); 
			
			//Если есть условие
			if ($where != "") $where = " WHERE (".$where.")";

			//Запрос к базе
			return $this->Exec($dbname, "UPDATE '$tablename' SET $set_data $where;");
		}
		
		
		//Добавить запись в таблицу
		public function Insert($dbname, $tablename, $rec)
		{
		
			if ( ($tablename == "") or (!is_array($rec)) )  return false;
			//Экранируем
			$tablename 	= $this->quote($tablename);
			
			/*
			//Получаем список полей таблицы
			$rowlist = $this->FieldList($dbname, $tablename); 
			
			if ((! isset($rec['id'])) and (isset($rowlist[0])) and ($rowlist[0]=='id')) unset($rowlist[0]); //Если поле id не указали в $rec_array, то и в список полей оно попасть не должно
			
			
			//Проходим по списку полей. Берем из $rec только те записи, которые носят названия полей в базе и заносим в переменную $REC,
			//паралельно экранируя значение
			foreach ($rowlist  as $key => $value)  
			{ 	
				//
				if (isset($rec[$value])) 
				{
					$XREC[$value] = &$rec[$value]; 			//Деалем ссылку из массива (передаваемые строки могут быть очень большими - мы экономим память)
					$XREC[$value] = $this->quote($XREC[$value]);	//Экранируем полученное значение
				
				}
					
			}
			
			$recstr = implode ("', '" , $XREC); //Собираем значения полей в одну строку
			$recstr = "'".$recstr."'";	//Обрамляем в ковычки
			
			$rowstr = implode ("', '" , array_keys($XREC)); //Собираем список полей таблицы в одну строку
			$rowstr = "'".$rowstr."'";	//Обрамляем в ковычки
			*/
			
			
			//ВЕРСИЯ 2. TODO: Без лишнего обращения к базе, но и без проверки на корректность полей
			//Проходим по списку полей, паралельно экранируя значение
			foreach ($rec  as $key => &$value)  
			{ 	
				$value = $this->quote($value);
			}
			
			$recstr = implode ("', '" , $rec); //Собираем значения полей в одну строку
			$recstr = "'".$recstr."'";	//Обрамляем в ковычки
			
			$rowstr = implode ("', '" , array_keys($rec)); //Собираем список полей таблицы в одну строку
			$rowstr = "'".$rowstr."'";	//Обрамляем в ковычки
			
			//Выполянем запрос
			return $this->Query($dbname, "INSERT INTO '$tablename'($rowstr) VALUES ($recstr);");	
		}
		
		//==============================================================================
		//				РАБОТА С ИНДЕКСАМИ
		//==============================================================================		
		 //Создание индекса	
		public function IndexCreate($dbname, $tablename, $indexname, $columns)
		{
			if (($dbname == "") or ($tablename == "") or ($indexname == "")) return false;
			//Если $columns массив - делаем из него строку, параллельно экранируя
			if (is_array($columns))
			{
				//Для начала экранируем это все
				foreach ($columns as $key => &$value)  $value = $this->quote($value);
				//Собираем поля в одну строку
				$columns = "'". implode ("', '" , $columns) ."'"; 
			}
			//Если columns строка - нам просто нечего не остается, как проэкранировать её
			else
			{
				$columns = $this->quote($columns);
			}
			
			//Экранируем
			$tablename 	= $this->quote($tablename);
			$indexname	= $this->quote($indexname);
			
			return $this->Query($dbname, "CREATE INDEX '$indexname' on '$tablename' ($columns);");
		}
		
		//Удаление индекса	 
		//TODO:tablename оставлен для совместимости с другими базами, поддерживающими одинаковые названия индексов в разных таблицах
		public function IndexDrop($dbname, $tablename, $indexname)
		{
			if (($dbname == "") /*or ($tablename == "")*/ or ($indexname == "")) return false;
			
			//Экранируем
			//$tablename 	= $this->quote($tablename);
			$indexname	= $this->quote($indexname);
			
			return $this->Query($dbname, "DROP INDEX '$indexname';");
		}
		
		//==============================================================================
		//				РАБОТА С ЗАПИСЯМИ
		//==============================================================================
		
		
		/*Добавляет запись $rec_array (которая представляет собой массив, в котором перечисленны все значения полей записи) в таблицу $tablename хранимую в базе $db; Предполагается, что первое поле является инкрементным ключем, поэтому не нужно его указывать в записи - нужно сразу перечислить все значения полей. 
$rec_array - массив с данными, которые должны быть добалены в базу. $rec_array - ассоциативный массив. Ключи ассоциативного массива должны соответствовать
полям в базе данных. Значение этих ключей - значениям, которые нужно добавить в базу. */
		public function RecAdd($dbname, $tablename, $rec)
		{
			//Добавить запись в таблицу
			return $this->Insert($dbname, $tablename, $rec);
		}
		
		//Удаление записи по id
		public function RecDel($dbname, $tablename, $id)
		{
			if (($tablename == "") or ($id == "")) return false;

			$tablename 	= $this->quote($tablename);
			$id		= $this->quote($id);

			//Выполянем запрос
			return $this->Query($dbname, "DELETE FROM '$tablename' WHERE id='$id';");	
		}		
		
		//Чтение записи записи по id
		public function RecRead($dbname, $tablename, $id)		
		{
			if (($tablename == "") or ($id == "")) return false;

			//Экранируем
			$tablename 	= $this->quote($tablename);
			$id		= $this->quote($id);
	
			//Чтение из базы
			$query = $this->Query($dbname, "SELECT * FROM '$tablename' WHERE id = '$id';");	
			return $query[0];
		}
		
		//Изменение записи
		public function RecEdit($dbname, $tablename, $id, $rec)
		{
		
		}
		
		
		//Изменение записи
		public function RecAll($dbname, $tablename, $sort="")
		{
			if (($dbname == "") or ($tablename == "")) return false;
			//Экранируем
			$tablename 	= $this->quote($tablename);
			$sort 	= $this->quote($sort);
			
			//Кавычки!!!!!!!!!!!!!! Если начать экранировать - эта сволочь умирает
			if ($sort != "") $sort = " ORDER BY $sort"; 
			
			//Чтение из базы
			return $this->Select($dbname, $tablename, '*', '', $sort);
		}
		
		
		
		
		
		
		//==============================================================================
		//				СЛУЖЕБНЫЕ МЕТОДЫ
		//==============================================================================
		
		
		//Логгер и принтер ошибок событий баз данных
		private function ErrorNotice($notice)
		{
			if ($this->Print_Notice)
			{
				echo $notice;
			}
		}
		
		//Генерирует полный путь к базе данных исходя из названия
		private function GeneratePathDataBase($dbname)
		{
			//выделяем имя файла
			$dbname = basename($dbname);
			//собираем полный путь к бд
			return $this->Folder_DataBase .DIRECTORY_SEPARATOR. $dbname; 	
		}
	
 
		
		//Экранирование
		public function quote(&$string)
		{
			//Экранирование через pdo
			//$buf = new PDO('sqlite:1.db');
			//return $buf->quote($string);
		
			//Колхозное экранирование
			return str_replace ("'" , "''" , $string);
		}



	
	}
