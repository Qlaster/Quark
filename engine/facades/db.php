<?php

	namespace unit\db;
	use QyberTech\ORM\QORM;

	error_reporting(E_ALL & ~E_NOTICE);

	//Примеры применения
	//$APP->db->connect('dbname')->table('tablename')->where('id = ?', $id)->select();



	# ---------------------------------------------------------------- #
	#                 РЕАЛИЗАЦИЯ   ИНТЕРФЕЙСА                          #
	# ---------------------------------------------------------------- #
	class DBConnect
	{
		public $config = [];
		private $ORMConnections;
		private $APP;

		function __construct($APP)
		{
			$this->APP		= $APP;
			$this->config 	= $APP->config->get(__file__);
		}

		public function connect($name)
		{
			//Если нет подключения
			if ( !isset($this->ORMConnections[$name]) )
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
						unset($connect[$key]);
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
						$this->ORMConnections[$name] = new \PDO("$type:$path/$base");
					}
					else
					{
						$this->ORMConnections[$name] = new \PDO("$type:host=$host;dbname=$base;$params", $user, $password);
					}
				}
				catch (PDOException $e)
				{
					echo $e;
					return null;
				}

				//вешаем orm интерфейс к подключению бд
				$this->ORMConnections[$name] = new QORM($this->ORMConnections[$name]);
			}

			return $this->ORMConnections[$name];
		}

		public function disconnect($name)
		{
			unset($this->ORMConnections[$name]);
		}

		public function connections()
		{
			return $this->ORMConnections;
		}

		public function listing()
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

	return new DBConnect($this);

