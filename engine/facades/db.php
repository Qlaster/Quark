<?php

	namespace App\Facade;
	use QyberTech\ORM\QORM;

	//~ error_reporting(E_ALL & ~E_NOTICE);

	//Примеры применения
	//$APP->db->connect('dbname')->table('tablename')->where('id = ?', $id)->select();



	# ---------------------------------------------------------------- #
	#                 РЕАЛИЗАЦИЯ   ИНТЕРФЕЙСА                          #
	# ---------------------------------------------------------------- #
	class DBConnect
	{
		public $config = [];
		private $ORMConnections = [];
		private $configInterface;

		function __construct($configInterface)
		{
			$this->configInterface	= $configInterface;
			$this->config 	= (array) $configInterface->get(__file__);
		}

		public function connect($name)
		{
			//Горячий путь — соединение уже установлено
			if (isset($this->ORMConnections[$name]))
				return $this->ORMConnections[$name];

			$connect = $this->config['connect'][$name] ?? null;
			if (!$connect) return false;

			$pdo = $this->createPDO($connect);
			if (!$pdo) return false;

			//вешаем orm интерфейс к подключению бд
			return $this->ORMConnections[$name] = new QORM($pdo);
		}

		private function createPDO($connect)
		{
			try
			{
				if ($connect['type'] == 'sqlite')
				{
					$path = $this->config['settings']['sqlite']['path'] ?? null;
					if (!$path) return null;

					$file = $path . DIRECTORY_SEPARATOR . $connect['dbname'];
					if (! file_exists($file)) return null;

					$pdo = new \PDO("sqlite:$file");

					//Прагмы из конфига: settings.sqlite.pragma.* → PRAGMA name = value
					foreach ((array) ($this->config['settings']['sqlite']['pragma'] ?? []) as $pragma => $value)
						if (preg_match('/^\w+$/', $pragma) and preg_match('/^[\w.-]+$/', (string) $value))
							$pdo->exec("PRAGMA $pragma = $value");

					return $pdo;
				}

				return new \PDO(
					"{$connect['type']}:host={$connect['host']};dbname={$connect['dbname']};{$connect['params']}",
					$connect['user'], $connect['password']
				);
			}
			catch (\PDOException $e)
			{
				//Без echo — exception содержит DSN и может содержать пароль
				trigger_error("DBConnect: ".$e->getMessage(), E_USER_WARNING);
				return null;
			}
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

		public function save()
		{
			$this->configInterface->set($this->config);
		}
	}



	# ---------------------------------------------------------------- #
	# --------------[ СОЗДАЕМ И ПОДКЛЮЧАЕМ ИНТЕРФЕЙС ]---------------- #
	# ---------------------------------------------------------------- #

	return new DBConnect($this->config);

