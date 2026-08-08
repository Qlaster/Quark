<?php

	/*
	 * user
	 *
	 * Пользователи админ-панели. Хранилище - sqlite:
	 * системные поля колонками, произвольные пользовательские поля в data (JSON).
	 * Пресеты прав доступа - в таблице presets той же базы.
	 *
	 * При первом запуске данные переносятся из легаси engine/database/users.dba
	 * (если доступно расширение dba), иначе создается пользователь по умолчанию.
	 * Легаси md5-хэши прозрачно переписываются на password_hash при входе.
	 *
	 */

	namespace App\Facade;

	# ---------------------------------------------------------------- #
	#                 РЕАЛИЗАЦИЯ   ИНТЕРФЕЙСА                          #
	# ---------------------------------------------------------------- #
	class CMSUser
	{
		//Системные поля — колонки таблицы. Всё, что не входит в список, уходит в data (JSON)
		private $columns 	= ['login', 'hash', 'name', 'email', 'info', 'logo', 'disable', 'access', 'denied'];
		//Колонки, хранящие сериализованные структуры (JSON)
		private $jsonCols 	= ['access', 'denied'];

		public $config;
		public $preset;

		private $pdo;
		private $table;
		private $loggedCache = null;

		public function __construct($pdo, $configInterface)
		{
			$this->configInterface = $configInterface;

			//Дефолтный конфиг, поверх накатываем ini
			$this->config['db']['pdo']   = 'sqlite:engine/database/users.sqlite';
			$this->config['db']['table'] = 'users';
			$this->config['default_user']['name']     = 'Root user';
			$this->config['default_user']['login']    = 'admin';
			$this->config['default_user']['password'] = 'quark';
			//Новый id сессии при логине (защита от session fixation)
			$this->config['session']['regenerate-id'] = true;
			//Время жизни сессии в секундах; 0 = наследовать php.ini/.htaccess
			$this->config['session']['lifetime']      = 0;

			$this->config = array_replace_recursive($this->config, (array) $configInterface->get(__file__));

			$this->table = $this->config['db']['table'];

			if ($pdo)
			{
				$this->pdo = $pdo;
			}
			else
			{
				//Одно подключение на фасад — sqlite сам разруливает конкурентность
				$this->pdo = new \PDO($this->config['db']['pdo']);
				$this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
			}

			$this->constructTable();

			//Таблица пресетов прав доступа
			$this->pdo->exec(
				"CREATE TABLE IF NOT EXISTS '".($this->config['db']['presets'] ?? 'presets')."' (
					'name'  TEXT PRIMARY KEY,
					'rules' TEXT
				)"
			);

			$this->preset = new UserPresets($this->pdo, $this->config['db']['presets'] ?? 'presets');

			//Разовый перенос пресетов из ini в таблицу (только если таблица пуста)
			if ($this->config['presets'] and !$this->preset->count())
				$this->preset->importIni($this->config['presets']);

			//Первый запуск: если юзеров нет вообще — переливаем легаси dba, либо создаём дефолтного
			if (!$this->all())
			{
				$this->importDba();
				if (!$this->all()) $this->createDefault();
			}
		}

		private function constructTable()
		{
			$table = $this->table;
			$this->pdo->exec(
				"CREATE TABLE IF NOT EXISTS '$table' (
					'login'   TEXT PRIMARY KEY,
					'hash'    TEXT,
					'name'    TEXT,
					'email'   TEXT,
					'info'    TEXT,
					'logo'    TEXT,
					'disable' INTEGER DEFAULT 0,
					'access'  TEXT,
					'denied'  TEXT,
					'data'    TEXT
				)"
			);
		}


		# ---------------------------------------------------------------- #
		#              УПАКОВКА / РАСПАКОВКА ЗАПИСИ                        #
		# ---------------------------------------------------------------- #

		//Массив пользователя -> строка таблицы
		private function pack($user)
		{
			//Пароль — write-only: в хранилище не попадает ни в колонках, ни в data
			unset($user['password']);

			$row = [];
			$data = [];

			foreach ($user as $key => $value)
			{
				if (!in_array($key, $this->columns))
				{
					//Произвольное пользовательское поле — в data
					$data[$key] = $value;
					continue;
				}
				$row[$key] = in_array($key, $this->jsonCols) ? json_encode($value) : $value;
			}

			$row['data'] = json_encode($data);
			return $row;
		}

		//Строка таблицы -> массив пользователя (та же форма, что отдавал dba)
		private function unpack($row)
		{
			if (!$row) return $row;

			$user = [];
			foreach ($row as $key => $value)
			{
				if ($key === 'data') continue;
				$user[$key] = in_array($key, $this->jsonCols) ? (array) json_decode($value, true) : $value;
			}

			//Пользовательские поля из data — не перекрывают системные колонки
			foreach ((array) json_decode($row['data'], true) as $key => $value)
				if (!in_array($key, $this->columns))
					$user[$key] = $value;

			return $user;
		}


		# ---------------------------------------------------------------- #
		#                     ХЭШИРОВАНИЕ                                  #
		# ---------------------------------------------------------------- #

		private function hash($password)
		{
			return password_hash($password, PASSWORD_DEFAULT);
		}

		//Проверка пароля. Вернет true, 'legacy' (легаси md5 - надо переписать хэш) или false
		private function verify($password, $hash)
		{
			//Легаси-хэш из users.dba — md5 (32 hex-символа)
			if (preg_match('/^[a-f0-9]{32}$/i', (string) $hash))
				return (md5($password) === $hash) ? 'legacy' : false;

			return password_verify($password, (string) $hash) ? true : false;
		}

		//Детерминированный токен сессии (password_hash пересчитать нельзя - он с солью)
		private function sessionToken($login, $hash)
		{
			return hash('sha256', $login.' '.$hash);
		}

		//Стартует сессию с учётом настроек [session]
		private function ensureSession()
		{
			if (session_status() !== PHP_SESSION_NONE) return;

			if ($lifetime = (int) ($this->config['session']['lifetime'] ?? 0))
			{
				session_set_cookie_params($lifetime);
				ini_set('session.gc_maxlifetime', (string) $lifetime);
			}
			session_start();
		}


		//Дополняет исходный массив необходимыми полями
		private function userCorrect(&$user)
		{
			$_user['login']   = '';
			$_user['hash']    = '';
			$_user['name']    = '';
			$_user['logo']    = '';
			$_user['email']   = '';
			$_user['info']    = '';
			$_user['disable'] = 0;
			$_user['access']  = [];
			$_user['denied']  = [];

			$user = array_merge($_user, $user);
			$user['login'] = mb_strtolower($user['login']);

			//Логин — ключ записи и часть файловых путей: без разделителей каталога и NUL
			if (strpbrk($user['login'], "/\\\0")) $user['login'] = '';
		}


		# ---------------------------------------------------------------- #
		#                     CRUD                                         #
		# ---------------------------------------------------------------- #

		public function add($user)
		{
			$this->userCorrect($user);
			if ($user['login'] == '') return false;

			if ($this->exists($user['login'])) return false;
			if ($user['password'] == '')       return false;

			$user['hash'] = $this->hash($user['password']);
			unset($user['password']);

			$row = $this->pack($user);
			$cols = implode(',', array_map(function($c){ return "'$c'"; }, array_keys($row)));
			$marks = implode(',', array_fill(0, count($row), '?'));

			$stmt = $this->pdo->prepare("INSERT INTO '{$this->table}' ($cols) VALUES ($marks)");
			return $stmt->execute(array_values($row));
		}

		public function edit($user)
		{
			$this->userCorrect($user);
			if ($user['login'] == '') return false;

			if (! $this->exists($user['login'])) return false;

			if ((isset($user['password'])) and ($user['password'] != ''))
			{
				$user['hash'] = $this->hash($user['password']);
				unset($user['password']);
			}

			if ($user['hash'] == '') return false;

			$row = $this->pack($user);
			unset($row['login']);

			$set = implode(', ', array_map(function($c){ return "'$c' = ?"; }, array_keys($row)));
			$stmt = $this->pdo->prepare("UPDATE '{$this->table}' SET $set WHERE login = ?");
			return $stmt->execute(array_merge(array_values($row), [$user['login']]));
		}

		public function get($login)
		{
			$stmt = $this->pdo->prepare("SELECT * FROM '{$this->table}' WHERE login = ?");
			$stmt->execute([mb_strtolower((string) $login)]);
			return $this->unpack($stmt->fetch(\PDO::FETCH_ASSOC));
		}

		public function exists($login)
		{
			$stmt = $this->pdo->prepare("SELECT 1 FROM '{$this->table}' WHERE login = ? LIMIT 1");
			$stmt->execute([mb_strtolower((string) $login)]);
			return (bool) $stmt->fetchColumn();
		}

		public function all()
		{
			$stmt = $this->pdo->query("SELECT * FROM '{$this->table}'");
			$result = [];
			foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row)
				$result[$row['login']] = $this->unpack($row);
			return $result;
		}

		public function del($login)
		{
			$stmt = $this->pdo->prepare("DELETE FROM '{$this->table}' WHERE login = ?");
			return $stmt->execute([mb_strtolower((string) $login)]);
		}


		# ---------------------------------------------------------------- #
		#                     СЕССИЯ / ДОСТУП                              #
		# ---------------------------------------------------------------- #

		public function login($login, $password)
		{
			$login = mb_strtolower($login);
			if ($password == '') return false;

			$user = $this->get($login);
			if (! $user) return false;

			//Если пользователь отключен
			if ($user['disable']) return false;

			$verify = $this->verify($password, $user['hash']);
			if ($verify === false) return false;

			//Прозрачная миграция легаси md5-хэша на password_hash
			if ($verify === 'legacy')
				$user['hash'] = $this->rehash($login, $password);

			$this->ensureSession();

			//Защита от session fixation: новый id при повышении привилегий
			if (($this->config['session']['regenerate-id'] ?? true) and session_status() === PHP_SESSION_ACTIVE)
				session_regenerate_id(true);

			$_SESSION['cms_login']    = $login;
			$_SESSION['cms_password'] = $this->sessionToken($user['login'], $user['hash']);
			return true;
		}

		//Переписывает хэш пользователя на password_hash. Возвращает новый хэш.
		private function rehash($login, $password)
		{
			$hash = $this->hash($password);
			$stmt = $this->pdo->prepare("UPDATE '{$this->table}' SET hash = ? WHERE login = ?");
			$stmt->execute([$hash, $login]);
			return $hash;
		}

		public function logged()
		{
			//Кеш на время запроса: autoinclude дёргает logged() многократно
			if ($this->loggedCache !== null) return $this->loggedCache;

			$this->ensureSession();

			if (!isset($_SESSION['cms_login']) or (!isset($_SESSION['cms_password']))) return false;

			$user = $this->get($_SESSION['cms_login']);
			if (! $user) return false;

			if (! hash_equals($this->sessionToken($user['login'], $user['hash']), $_SESSION['cms_password'])) return false;

			return $this->loggedCache = $user;
		}

		public function logout()
		{
			$this->loggedCache = null;
			unset($_SESSION['cms_login']);
			unset($_SESSION['cms_password']);
		}

		public function access($access_item=null)
		{
			$user = $this->logged();
			if (! $user) return false;

			if (isset($user['access'][$access_item])) return $user['access'][$access_item];
			return null;
		}

		public function denied($access_item=null)
		{
			$user = $this->logged();
			if (! $user) return false;

			if (isset($user['denied'][$access_item])) return $user['denied'][$access_item];
			return null;
		}

		public function createDefault()
		{
			$user = $this->config['default_user'];
			$this->add($user);
		}


		# ---------------------------------------------------------------- #
		#            МИГРАЦИЯ С ЛЕГАСИ users.dba                           #
		# ---------------------------------------------------------------- #

		//Переносит записи из старого users.dba в таблицу. Вернет число импортированных или false
		public function importDba($dbaFile='engine/database/users.dba')
		{
			if (! function_exists('dba_open') or ! file_exists($dbaFile)) return false;

			//Подбираем доступный handler
			$handler = null;
			foreach ((array) dba_handlers(true) as $h)
				if (in_array($h, ['db4', 'gdbm', 'flatfile', 'inifile'])) { $handler = $h; break; }
			if (!$handler) return false;

			$h = @dba_open($dbaFile, 'r', $handler);
			if (!$h) return false;

			$count = 0;
			for ($key = dba_firstkey($h); $key !== false; $key = dba_nextkey($h))
			{
				$user = unserialize(dba_fetch($key, $h));
				if (!is_array($user) or $this->exists($key)) continue;

				//Легаси-поле 'mail' унифицируем в 'email'
				if (isset($user['mail']) and !isset($user['email'])) $user['email'] = $user['mail'];
				unset($user['mail'], $user['password']);

				//md5-хэш переносим как есть — смигрирует на password_hash при первом входе
				$user['login'] = $key;
				$row = $this->pack($user);

				$cols  = implode(',', array_map(function($c){ return "'$c'"; }, array_keys($row)));
				$marks = implode(',', array_fill(0, count($row), '?'));
				$this->pdo->prepare("INSERT OR IGNORE INTO '{$this->table}' ($cols) VALUES ($marks)")
						  ->execute(array_values($row));
				$count++;
			}
			dba_close($h);
			return $count;
		}
	}


	# ---------------------------------------------------------------- #
	#                  ПРЕСЕТЫ ПРАВ ДОСТУПА                            #
	# ---------------------------------------------------------------- #
	class UserPresets
	{
		private $pdo;
		private $table;

		public function __construct($pdo, $table='presets')
		{
			$this->pdo   = $pdo;
			$this->table = $table;
		}

		//$APP->users->preset->get()        - все пресеты (name => rules)
		//$APP->users->preset->get($name)   - правила одного пресета
		function get($name=null)
		{
			if ($name !== null)
			{
				$stmt = $this->pdo->prepare("SELECT rules FROM '{$this->table}' WHERE name = ?");
				$stmt->execute([$name]);
				$rules = $stmt->fetchColumn();
				return $rules === false ? null : json_decode($rules, true);
			}

			$presets = [];
			foreach ($this->pdo->query("SELECT name, rules FROM '{$this->table}'") as $row)
				$presets[$row['name']] = json_decode($row['rules'], true);
			return $presets;
		}

		//$APP->users->preset->set($name, $rules)
		function set($name, $rules)
		{
			$stmt = $this->pdo->prepare("INSERT OR REPLACE INTO '{$this->table}' (name, rules) VALUES (?, ?)");
			return $stmt->execute([$name, json_encode($rules)]);
		}

		function delete($name)
		{
			$stmt = $this->pdo->prepare("DELETE FROM '{$this->table}' WHERE name = ?");
			return $stmt->execute([$name]);
		}

		function rename($oldName, $newName)
		{
			$stmt = $this->pdo->prepare("UPDATE '{$this->table}' SET name = ? WHERE name = ?");
			return $stmt->execute([$newName, $oldName]);
		}

		function count()
		{
			return (int) $this->pdo->query("SELECT COUNT(*) FROM '{$this->table}'")->fetchColumn();
		}

		//Перенос пресетов из ini-секции в таблицу (значения в ini - JSON-строки)
		function importIni($presets)
		{
			foreach ((array) $presets as $name => $rules)
				$this->set($name, is_string($rules) ? json_decode($rules, true) : $rules);
		}
	}



	# ---------------------------------------------------------------- #
	# --------------[ СОЗДАЕМ И ПОДКЛЮЧАЕМ ИНТЕРФЕЙС ]---------------- #
	# ---------------------------------------------------------------- #

	return new CMSUser(null, $this->config);
