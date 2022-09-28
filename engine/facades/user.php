<?php

	namespace unit\user;

	# ---------------------------------------------------------------- #
	#                  ОПИСАНИЕ     ИНТЕРФЕЙСА                         #
	# ---------------------------------------------------------------- #
	interface QAdminUserInterface
	{	
		// Добавление нового пользователя
		public function add($user);
		
		// Редактирование пользователя
		public function	edit($user);
		
		// Запрашивает пользователя по логину
		public function get($login);
		
		// Проверяет наличие пользователя по логину
		public function exists($login);
		
		// Вернет весь список пользователей
		public function all();
		
		// Удаление пользователя по логину
		public function del($login);
		
		// Авторизация пользователя
		public function login($login, $password);
		
		// Вернет пользователя, который авторизирован
		public function	logged();
		
		// Завершает сессию авторезированного пользователя
		public function logout();
		
		// Проверяет права доступа на операцию вошедшего пользователя
		public function access();
	}

	# ---------------------------------------------------------------- #
	#                 РЕАЛИЗАЦИЯ   ИНТЕРФЕЙСА                          #
	# ---------------------------------------------------------------- #
	class CMS_User implements QAdminUserInterface
	{
		private $dba_interface;
		public $config;
		
		public function __construct($dba_interface, $config=null)
		{			
			//Берем интерейс nosql базы данных
			$this->dba_interface = $dba_interface;
			//Устанавливаем дефолтный конфиг
			$this->config['database'] = 'users.dba';
			$this->config['default_user']['name'] = 'Root user';
			$this->config['default_user']['login'] = 'admin';
			$this->config['default_user']['password'] = 'admin';
			
			if ($config) $this->config = $config;
			
			if (!$this->exists($config['default_user']['login']))
				$this->create_default();
			if (! session_id()) session_start();
		}
		
		// Возвращает указатель на интерфейс упрвления базой данных
		private function db()
		{
			return $this->dba_interface->db($this->config['database']);
		}
		 
		
		//Дополняет исходный массив необходимыми полями
		private function user_correct(&$user)
		{
			$_user['login'] = '';
			//$_user['password'] = '';					
			$_user['hash'] = '';
			$_user['name'] = '';
			$_user['logo'] = '';
			$_user['mail'] = '';			
			$_user['info'] = '';
			$_user['disable'] = '';			
			$_user['access'] = array();
			
			//return array_merge($_user, $user);
			$user = array_merge($_user, $user);
			$user['login'] = mb_strtolower($user['login']);
			//return $user;
		}
		
		private function hash($password)
		{
			return md5($password);
		}
		
		
		
		/*
		 * 
		 * name: добавление нового пользователя
		 * @param
		 * @return
		 * 
		 */
		public function add($user)
		{
			if ($user['login'] == '') 
			{
				trigger_error ( "Enter the login!" , E_USER_WARNING ); 
				return false;
			}
			$this->user_correct($user);
				
			if ($this->exists($user['login']))
			{
				$login = $user['login'];
				trigger_error ( "User '$login' already exists!" , E_USER_WARNING ); 
				return false;
			}
		
			if ($user['password'] == '') 
			{
				//throw new Exception('Enter the password!');
				trigger_error ( "Enter the user password!" , E_USER_WARNING ); 
				return false;
			}
			
			$user['hash'] = $this->hash($user['password']);
			unset($user['password']);
			$this->db()->insert($user['login'], $user);
		}
		
		
		/*
		 * 
		 * name: Редактирование пользователя
		 * @param
		 * @return
		 * 
		 */
		public function edit($user)
		{
			if ($user['login'] == '') 
			{
				trigger_error ( "Enter the login!" , E_USER_WARNING ); 
				return false;
			}
			$this->user_correct($user);	
			
			if (! $this->exists($user['login']))
			{
				$login = $user['login'];
				trigger_error ( "User '$login' does not exist!" , E_USER_WARNING ); 
				return false;
			}
			
			if ((isset($user['password'])) and ($user['password'] != ''))
			{
				$user['hash'] = $this->hash($user['password']);
				unset($user['password']);
			}
			
			if ( (!isset($user['hash'])) or ($user['hash'] == ''))
			{
				trigger_error ( "Enter the user password!" , E_USER_WARNING ); 
				return false;
			}
		
			
			$this->user_correct($user);
			return $this->db()->update($user['login'], $user);
		}
		
		
		
		public function get($login)
		{
			return $this->db()->select(mb_strtolower($login));
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
			return $this->db()->where('id = ?', $id)->Select();
		}
		
		/*
		 * 
		 * name: Проверяет наличие пользователя
		 * @param
		 * @return
		 * 
		 */
		public function exists($login)
		{
			return $this->db()->exists(mb_strtolower($login));
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
			
			//Получаем имя базы, в которой храним коллекцию
			//$filename = $this->db_filename();
			
			//Если базы с коллекцией не существует - даже нет смысла выполнять код дальше - просто вернем пустой массив
			if (! $this->dba_interface->db_exists($this->config['database'])) return array();
						
			
			return (array) $this->dba_interface->all();	
		}
		
		
		
		/*
		 * 
		 * name: Удаление
		 * @param
		 * @return
		 * 
		 */
		public function del($login)
		{
			$this->db()->delete(mb_strtolower($login));
		}
		
		/*
		 * 
		 * name: Авторезирует пользователя.
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

			if ($password == '') 
			{
				//trigger_error ( "Enter the password!" , E_USER_WARNING ); 
				return false;
			}
						
			$user = $this->get($login);
			
			//Если пользователь отключен
			if ($user['disable']) return false;
			
			
			//Если верификация пройдена
			if ($this->hash($password) == $user['hash'])
			{
				$_SESSION['cms_login'] = $login;
				$_SESSION['cms_password'] = $this->hash($login.' '.$user['hash']);
				return true;
			}
						
			return false;
		}
		
		
		/*
		 * 
		 * name: Вернет пользователя, который авторизирован
		 * @param
		 * @return
		 * 
		 */
		public function	logged()
		{
			if (!isset($_SESSION['cms_login']) or (!isset($_SESSION['cms_password']))) return false;
			
			$user = $this->get($_SESSION['cms_login']);
			if (! $user) return false;
			
			if ($this->hash($user['login'].' '.$user['hash']) !== $_SESSION['cms_password']) return false;
			return $user;					
		}
		
		
		/*
		 * 
		 * name: Завершает сессию авторезированного пользователя
		 * @param
		 * @return
		 * 
		 */

		public function logout()
		{
			unset($_SESSION['cms_login']);
			unset($_SESSION['cms_password']);
		}
		
		
		
		/*
		 * 
		 * name: Проверяет права доступа на операцию вошедшего пользователя
		 * @param
		 * @return
		 * 
		 */
		public function access($access_item=null)
		{
			$user = $this->logged();
			if (! $user) return false;
			
			if (isset($user['access'][$access_item])) return $user['access'][$access_item];
			return null;
		}
		
		
		/*
		 * 
		 * name: Проверяет запреты на исполнение контроллеров вошедшего пользователя
		 * @param
		 * @return
		 * 
		 */
		public function denied($access_item=null)
		{
			$user = $this->logged();
			if (! $user) return false;
			
			if (isset($user['denied'][$access_item])) return $user['denied'][$access_item];
			return null;
		}
		
		public function create_default()
		{
			$user = $this->config['default_user'];
			$this->add($user);
		}
		
	}
	

	# ---------------------------------------------------------------- #
	# --------------[ СОЗДАЕМ И ПОДКЛЮЧАЕМ ИНТЕРФЕЙС ]---------------- #
	# ---------------------------------------------------------------- #
	
	return new CMS_User($this->dba, $this->config->get(__file__));
