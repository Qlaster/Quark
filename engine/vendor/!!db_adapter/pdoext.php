<?php



		/**
				Модуль, расширяющий возможности PDO

		 

		//MóSQL
		$DBH = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass); 

		//PostgreSQL
		$DBH = new PDO("pgsql:host=$host;dbname=$dbname", $user, $pass);

		//MS SQL
		$DBH = new PDO("mssql:host=$host;dbname=$dbname", $user, $pass); 

		//SQLite
		$DBH = new PDO("sqlite:my/database/path/database.db");
	
		*/


	class PDOext extends PDO
	{
		
		
		public function __construct($dsn, $username='', $password='', $driver_options=array())
		{
			parent::__construct($dsn, $username, $password, $driver_options);

			//PDO будет находится в режиме исключений
			$this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			//PDO будет возвращать только ассоциативные массивы
			$this->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
			
			//$this->setAttribute(PDO::ATTR_STATEMENT_CLASS, array('DBStatement', array($this)));
		}
		
		
		//Метод execute возвращает бесполезное логическое значение об успехе операции, а не объект DBStatement.
		//Допиливаем свой метод execute.			
		public function execute($data=array())
		{
			parent::execute($data);
			return $this; 
		}
		
		
	}
	
	
	
