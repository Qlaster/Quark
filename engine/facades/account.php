<?php

	/*
	 * URL
	 *
	 * Управление пользователями
	 * Регистрация, авторизация и прочие функции реализации аккаунт-менеджмента
	 *
	 *
	 * Version 1.0
	 * Copyright 2022
	 *
	*/

	namespace unit\account;

	# ---------------------------------------------------------------- #
	#                  ОПИСАНИЕ     ИНТЕРФЕЙСА                         #
	# ---------------------------------------------------------------- #
	interface QAccountInterface
	{
		function __construct($orm_interface, $config);

		// Авторизирует пользователя
		public function login($login, $password);

		// Вернет пользователя, который авторизирован (Если известен session_id - можно провести авторизацию по нему)
		public function	logged($session_id=null);

		// Завершает сессию авторезированного пользователя
		public function logout($session_id=null);

		// Вывести всех пользователей
		public function all();

		// Проверяет наличие пользователя
		public function exists($login);

		// Получить пользователя по login
		public function get($login);

		// Добавить пользователя
		public function add($user);

		// Редактирует пользователя
		public function edit($user);

		// Удаление пользователя
		public function delete($login);
	}


	# ---------------------------------------------------------------- #
	#                            ПРИМЕСИ                               #
	# ---------------------------------------------------------------- #
	trait AccountExtensions
	{
		function accountIsBlocked($login)
		{
			$serviceRecord = $this->db('service')->where(['login'=>$login, 'ip'=>$this->client_ip()])->select();
			$serviceRecord = current($serviceRecord);
			//Нет сервисных упоминаний
			if (!$serviceRecord) return false;
			//Если превышено количество попыток авторизации и действует время блокировки аккаунта
			return (bool) ($serviceRecord['updated']+$this->config['protection']['blocked-time'] > $_SERVER['REQUEST_TIME']) and ($serviceRecord['attempt'] >= $this->config['protection']['max-failed-login']);
		}

		function serviceInfoClear($login)
		{
			return $this->db('service')->where(['login'=>$login])->delete();
		}

		function serviceAttemptFailFix($login)
		{
			$where = ['login'=>$login, 'ip'=>$this->client_ip()];
			$serviceRecord = $this->db('service')->where($where)->select();
			$serviceRecord = current($serviceRecord);

			$replaceRecord['login'] = $where['login'];
			$replaceRecord['ip']    = $where['ip'];
			$replaceRecord['attempt'] = $serviceRecord['attempt']+1;
			$replaceRecord['updated'] = $_SERVER['REQUEST_TIME'];

			return $serviceRecord ? $this->db('service')->where(['id'=>$serviceRecord['id']])->update($replaceRecord) : $this->db('service')->insert($replaceRecord);
		}

		function serviceAttemptInc($login)
		{
			$tablename = $this->config['db']['service'];
			return $this->db('service')->SQL("UPDATE \"$tablename\" SET 'attempt' = 'attempt' + 1");
		}
	}


	# ---------------------------------------------------------------- #
	#                 РЕАЛИЗАЦИЯ   ИНТЕРФЕЙСА                          #
	# ---------------------------------------------------------------- #
	class AccountUser implements QAccountInterface
	{
		use AccountExtensions;
		public $orm_interface;
		protected $tableColumns = array(
			'id'      => 'integer PRIMARY KEY AUTOINCREMENT',
			'login'   => 'text',     //Логин пользователя
			'name'    => 'text',     //Псевдоним пользователя
			'head'    => 'text',     //Заголовок учетной записи
			'hash'    => 'text',     //Хеш пароля
			'access'  => 'text',     //Список прав
			'service' => 'text',     //Сервисное поле на усмотрение разработчика
			'image'   => 'text',     //Лого
			'info'    => 'text',     //Описание
			'disable' => 'boolean',  //Флаг отключения
			'online'  => 'integer'); //unixtime последней активности

		protected $serviceColumns = array(
			'id'      => 'integer PRIMARY KEY AUTOINCREMENT',
			'login'   => 'text',     //Логин пользователя
			'ip'      => 'text',     //ip адрес, с которого выполнялось попытка входа
			'attempt' => 'integer',  //Количество попыток авторизации
			'updated' => 'integer'); //unixtime последнего изменения


		function __construct($orm_interface, $config)
		{
			if (!isset($orm_interface))
			{
				trigger_error('DB not unavailable', E_USER_ERROR);
				return false;
			}

			$this->orm_interface = $orm_interface;
			$this->config = $config;

			//Создадим табличку, если она еще не готова
			$this->construct_table();
		}


		/*
		 *
		 * name: Механизм получения доступа к таблице с пользователями
		 * @param
		 * @return
		 *
		 */
		public function db($tableName='table')
		{
			return $this->orm_interface->table($this->config['db'][$tableName]);
		}


		/*
		 *
		 * name: Построитель таблицы
		 * @param
		 * @return
		 *
		 */
		private function construct_table()
		{
			//Создаем таблицу с перечислением страниц
			$this->db()->Create($this->tableColumns);
			//Создаем табличку со служебной информацией
			$this->db('service')->Create($this->serviceColumns);
		}


		/*
		 *
		 * name: Алгоритм вычисления слепков пароля
		 * @param
		 * @return
		 *
		 */
		private function hash($password)
		{
			return md5($password);
		}


		/*
		 *
		 * name: Валидирует поля и производит необходимые преобразования
		 * @param
		 * @return
		 *
		 */
		public function user_correct(&$user)
		{
			if ($user['login']) $user['login'] = mb_strtolower($user['login']);
			if ($user['password'])
			{
				$user['hash'] = $this->hash($user['password']);
				unset($user['password']);
			}
		}

		/*
		 *
		 * name: Авторизирует пользователя
		 * @param
		 * @return
		 *
		 */
		public function login($login, $password)
		{
			$login = mb_strtolower($login);

			if (! $this->exists($login))
			{
				//trigger_error ( "User does not exist!" , E_USER_WARNING );
				return false;
			}

			if (! $user = $this->get($login) ) return false;


			//Если пользователь отключен
			if ($user['disable']) return false;

			//Проверим, не попадает ли текущая попытка авторизации под блокировку?
			if ($this->accountIsBlocked($login)) return false;

			//Если верификация пройдена
			if ($this->hash($password) == $user['hash'])
			{
				session_start();
				$_SESSION['login'] = $login;
				//Автризация успешна - почистим сервисные записи
				$this->serviceInfoClear($login);
				//Если всегда нужно генерировать новый id
				if (boolval($this->config['session']['regenerate-id'])) session_regenerate_id(true);
				//~ if (boolval($this->config['session']['regenerate-id'])) session_create_id();
				//Вернем идентификатор сессии
				return session_id();
			}

			//Фиксируем неудачную попытку авторизации (увеличим счетчик попыток на 1)
			$this->serviceAttemptFailFix($login);
			return false;
		}


		/*
		 *
		 * name: Вернет пользователя, который авторизирован (Если известен session_id - можно провести авторизацию по нему)
		 * @param
		 * @return
		 *
		 */
		public function	logged($session_id=null)
		{
			if ($session_id) session_id($session_id);
			session_start();
			return $this->get($_SESSION['login']);
		}


		/*
		 *
		 * name: Завершает сессию авторезированного пользователя
		 * @param
		 * @return
		 *
		 */
		public function logout($session_id=null)
		{
			if ($session_id) session_id($session_id);
			session_start();
			session_destroy();
			session_register_shutdown();
		}


		/*
		 *
		 * name: Вывести всех пользователей
		 * @param
		 * @return
		 *
		 */
		public function all()
		{
			return $this->db()->orderBy('name ASC')->select();
		}


		/*
		 *
		 * name: Проверяет наличие пользователя
		 * @param $login  (string)
		 * @return (bool)
		 *
		 */
		public function exists($login)
		{
			return (bool) $this->db()->where('login = ?', mb_strtolower($login))->select('login');
		}


		/*
		 *
		 * name: Получить пользователя по login
		 * @param $login  (string)
		 * @return array account
		 *
		 */
		public function get($login)
		{
			if ($login)
				return $this->db()->where('login = ?', mb_strtolower($login))->select()[0];
		}


		/*
		 *
		 * name: Получить пользователя по id
		 * @param
		 * @return
		 *
		 */
		public function get_id($id)
		{
			if ($id)
				return $this->db()->where('id = ?', $id)->Select()[0];
		}



		/*
		 *
		 * name: Добавить пользователя
		 * @param
		 * @return
		 *
		 */
		public function add($user)
		{
			$this->user_correct($user);
			return $this->db()->Insert($user);
		}


		/*
		 *
		 * name: Редактирует пользователя
		 * @param
		 * @return
		 *
		 */
		public function edit($user)
		{
			$this->user_correct($user);
			return $this->db()->where('login = ?', $user['login'])->Update($user);
		}

		/*
		 *
		 * name: Удаление
		 * @param
		 * @return
		 *
		 */
		public function delete($login)
		{
			return $this->db()->where('login = ?', $login)->Delete();
		}

		/*
		 *
		 * name: Получить последнего зарегистрированного пользователя
		 * @param
		 * @return
		 *
		 */
		public function last()
		{
			$table = $this->config['db']['table'];
			return $this->db()->where("id = ( SELECT MAX(id) FROM \"$table\" )")->Select()[0];
		}

		/*
		 *
		 * name: Получить ip адрес клиента
		 * @param
		 * @return (string) ip address
		 *
		 */
		public function client_ip()
		{
			$ipaddress = '';
			if (getenv('HTTP_CLIENT_IP'))
				$ipaddress = getenv('HTTP_CLIENT_IP');
			else if(getenv('HTTP_X_FORWARDED_FOR'))
				$ipaddress = getenv('HTTP_X_FORWARDED_FOR');
			else if(getenv('HTTP_X_FORWARDED'))
				$ipaddress = getenv('HTTP_X_FORWARDED');
			else if(getenv('HTTP_FORWARDED_FOR'))
				$ipaddress = getenv('HTTP_FORWARDED_FOR');
			else if(getenv('HTTP_FORWARDED'))
			   $ipaddress = getenv('HTTP_FORWARDED');
			else if(getenv('REMOTE_ADDR'))
				$ipaddress = getenv('REMOTE_ADDR');
			else
				$ipaddress = 'UNKNOWN';
			return $ipaddress;
		}
	}


	# ---------------------------------------------------------------- #
	# --------------[ СОЗДАЕМ И ПОДКЛЮЧАЕМ ИНТЕРФЕЙС ]---------------- #
	# ---------------------------------------------------------------- #

	$config = $this->config->get(__file__);
	return new AccountUser($this->db->connect($config['db']['name']), $config);

