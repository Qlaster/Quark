<?php

	namespace QyberTech\ContentManager;


	class QMeta
	{

		//Имя таблицы для метаданных
		public $table = '';

		function __construct($PDO_interface, $table='meta')
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
			$this->table = $table;

			//- - - - - - - - - - - - - - - - - - - - - - - - - - - - -
			//Создаем таблицу c фрагментами (включениями), для кодов, метрик и т.д
			//- - - - - - - - - - - - - - - - - - - - - - - - - - - - -
			$stmt = $this->PDO_INTERFACE->prepare(
				"CREATE TABLE IF NOT EXISTS '$table'
					(
						'urlmask' UNIQUE,
						'name' UNIQUE,
						'data',
						'lang' UNIQUE,
						PRIMARY KEY('name', 'urlmask', 'lang')
					);
				")->execute();
			//Создаем индекс
			$this->PDO_INTERFACE->prepare("CREATE INDEX IF NOT EXISTS 'index_meta' on '$table' ('urlmask');")->execute();
		}


		protected function wherePrepare($args=[])
		{
			//Собираем условие запроса
			foreach ($args as $key => $value)
			{
				$where .= "\"$key\" = ? and";
				$values[] = $value;
			}
			$result[] = $where ? trim('WHERE '.$where, 'and') : '';
			$result[] = $values;

			return $result;
		}


		public function insert($record)
		{
			$table = $this->table;
			//Вытягиваем колонки
			$columns = implode('","', array_keys($record));
			//Приклеиваем указатели переменным
			$values = str_pad('', count($record)*2-1, '?,');
			//Отдаем запрос
			$stmt = $this->PDO_INTERFACE->prepare("INSERT INTO $table (\"$columns\") values ($values);");
			//отправляем запрос на выполнение
			return $stmt->execute(array_values($record));
		}

		public function replace($record)
		{
			$table = $this->table;
			//Вытягиваем колонки
			$columns = implode('","', array_keys($record));
			//Приклеиваем указатели переменным
			$values = str_pad('', count($record)*2-1, '?,');
			//Отдаем запрос
			$stmt = $this->PDO_INTERFACE->prepare("REPLACE INTO $table (\"$columns\") values ($values);");
			//отправляем запрос на выполнение
			return $stmt->execute(array_values($record));
		}


		public function select($args=[])
		{
			$table = $this->table;
			list ($where, $values) = $this->wherePrepare($args);
			$STH = $this->PDO_INTERFACE->prepare("SELECT * FROM '$table' $where;");
			$STH->execute($values);
			return $STH->fetchAll();
		}


		public function delete($where)
		{
			$table = $this->table;
			list ($where, $values) = $this->wherePrepare($args);
			$STH = $this->PDO_INTERFACE->prepare("DELETE * FROM '$table' $where;");
			return $STH->execute($values);
		}
	}
