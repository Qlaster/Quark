<?php

	//~ lib('qyber.tech/db/dbal.php');
	//~ lib('QyberTech\ORM\QORM');
	use QyberTech\ORM\QORM;	
	error_reporting(E_ALL & ~E_NOTICE);
	
	//Примеры применения
	//$APP->db->connect('dbname')->table('tablename')->where('id = ?', $id)->select();
	
	
	
	# ---------------------------------------------------------------- #
	#                 РЕАЛИЗАЦИЯ   ИНТЕРФЕЙСА                          #
	# ---------------------------------------------------------------- #
	class DB_interface
	{
		public $config = array();
		private $PDO_INTERFACE;
		private $APP;
		
		function __construct($APP)
		{
			$this->APP		= $APP;
			$this->config 	= $APP->config->get(__file__);
		}
		
		public function connect($name)
		{
			//Если нет подключения
			if ( !isset($this->PDO_INTERFACE[$name]) )
			{
				//Если нет настроек для текущей базы
				if ( !isset($this->config['connect'][$name]) )
				{
					return false;
				}
				
				$connect = $this->config['connect'][$name];

				//Удаляем лишние ключи
				foreach ($connect as $key => &$value)
				{
					if (!$value) 
					{
						unset($connect[$key]);
					}
					else
					{
						
					}
				}

				$type 		= (string) $connect['type'];
				$host 		= (string) $connect['host'];
				$base 		= (string) $connect['dbname'];
				$user 		= (string) $connect['user'];
				$password 	= (string) $connect['password'];
				$params 	= (string) $connect['params'];
				
				try 
				{				
					if ($type == 'sqlite')
					{
						$path = $this->config['settings']['sqlite']['path'];
						if (! file_exists("$path/$base")) return null;
						$this->PDO_INTERFACE[$name] = new \PDO("$type:$path/$base");
					}
					else
					{
						$this->PDO_INTERFACE[$name] = new \PDO("$type:host=$host;dbname=$base;$params", $user, $password);
					}	
				}
				catch (PDOException $e) 
				{			
					echo $e;
					return null;
				}
								
				//вешаем orm интерфейс к подключению бд
				$this->ORM_INTERFACE[$name] = new QORM($this->PDO_INTERFACE[$name]);
			}
			
			return $this->ORM_INTERFACE[$name];
			//~ $c = new PDO('dblib:host=your_hostname;dbname=your_db;charset=UTF-8', $user, $pass);
			//~ $dbh = new PDO('sqlite:/tmp/foo.db'); // success
		}
		
		public function connects()
		{
			return $this->PDO_INTERFACE;		
		}
		
		public function list()
		{
			return $this->config['connect'];
		}
		
		public function connects_save()
		{
			$this->APP->config->set($this->config);
		}
		
		public function config_save()
		{
			$this->connects_save();
		}
		
		public function connects_add()
		{
			
		}
		
		public function connects_del($name)
		{
			unset($this->config[$name]);
		}
	}



	# ---------------------------------------------------------------- #
	# --------------[ СОЗДАЕМ И ПОДКЛЮЧАЕМ ИНТЕРФЕЙС ]---------------- #
	# ---------------------------------------------------------------- #

	return new db_interface($this);

