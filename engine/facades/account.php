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
	#                 РЕАЛИЗАЦИЯ   ИНТЕРФЕЙСА                          #
	# ---------------------------------------------------------------- #
	class AccountUser implements QAccountInterface
	{

		public $orm_interface;
		protected $tableColumns = array(
			'id'      => 'integer PRIMARY KEY AUTOINCREMENT',
			'login'   => 'text',
			'name'    => 'text',
			'head'    => 'text',
			'hash'    => 'text',
			'access'  => 'text',
			'service' => 'text',
			'image'   => 'text',
			'info'    => 'text',
			'disable' => 'boolean',
			'online'  => 'integer');


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
		public function db()
		{
			return $this->orm_interface->table($this->config['db']['table']);
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

			$user = $this->get($login);

			//Если пользователь отключен
			if ($user['disable']) return false;

			//Если верификация пройдена
			if ($this->hash($password) == $user['hash'])
			{
				session_start();
				$_SESSION['login'] = $login;
				return session_id();
			}

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
				return $this->db()->where('login = ?', mb_strtolower($login))->select('login')[0];
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
			user_correct($user);
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
			user_correct($user);
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

	}


	# ---------------------------------------------------------------- #
	# --------------[ СОЗДАЕМ И ПОДКЛЮЧАЕМ ИНТЕРФЕЙС ]---------------- #
	# ---------------------------------------------------------------- #

	$config = $this->config->get(__file__);
	return new AccountUser($this->db->connect($config['db']['name']), $config);

