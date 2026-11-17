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
		public $role;
		public $preset;	//легаси-алиас $role — внешний код мог вызывать user->preset
		public $policy;

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

			//Политика безопасности — секция [policy]
			$this->config['policy']['ip']['allow']        = '*';
			$this->config['policy']['ip']['ban']          = 0;	//0 — бан отключён
			$this->config['policy']['ip']['bantime']      = 0;	//0 — перманентно
			$this->config['policy']['lock']['attempts']   = 0;	//0 — лок отключён
			$this->config['policy']['lock']['minutes']    = 0;
			$this->config['policy']['password']['length'] = 0;
			$this->config['policy']['password']['classes']= [];
			$this->config['policy']['password']['hash']   = 'default';

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

			//Таблица ролей (наборов прав). Имя из [db] roles; старый ключ
			//[db] presets читается для совместимости
			$rolesTable = $this->config['db']['roles'] ?? $this->config['db']['presets'] ?? 'roles';

			//Миграция легаси-хранилища: presets → roles (одноразовая)
			if ($rolesTable !== 'presets')
			{
				$old = $this->pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='presets'")->fetchColumn();
				$new = $this->pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='$rolesTable'")->fetchColumn();
				if ($old and !$new)
					$this->pdo->exec("ALTER TABLE 'presets' RENAME TO '$rolesTable'");
			}

			$this->pdo->exec(
				"CREATE TABLE IF NOT EXISTS '$rolesTable' (
					'name'  TEXT PRIMARY KEY,
					'rules' TEXT
				)"
			);

			$this->policy = new UserPolicy($this->pdo, (array) ($this->config['policy'] ?? []));

			$this->role = new UserRoles($this->pdo, $rolesTable);
			$this->preset = $this->role;	//легаси-алиас

			//Разовый перенос ролей из ini в таблицу (только если таблица пуста)
			$rolesIni = $this->config['roles'] ?? $this->config['presets'] ?? null;
			if (!empty($rolesIni) and !$this->role->count())
				$this->role->importIni($rolesIni);

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
			$algo = mb_strtolower((string) ($this->config['policy']['password']['hash'] ?? 'default'));

			if ($algo === 'md5') return md5($password);

			$map = [
				'default'  => PASSWORD_DEFAULT,
				'bcrypt'   => PASSWORD_BCRYPT,
				'argon2i'  => defined('PASSWORD_ARGON2I')  ? PASSWORD_ARGON2I  : PASSWORD_DEFAULT,
				'argon2id' => defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_DEFAULT,
			];

			return password_hash($password, $map[$algo] ?? PASSWORD_DEFAULT);
		}

		//Проверка пароля. Вернет true, 'legacy' (легаси md5 - надо переписать хэш) или false
		private function verify($password, $hash)
		{
			//Легаси-хэш из users.dba — md5 (32 hex-символа)
			if (preg_match('/^[a-f0-9]{32}$/i', (string) $hash))
				return (md5($password) === $hash) ? 'legacy' : false;

			return password_verify($password, (string) $hash) ? true : false;
		}

		//Детерминированная сигнатура сессии (password_hash пересчитать нельзя - он с солью).
		//Смена пароля → хэш другой → сигнатура в старых сессиях расходится → разлогин
		private function sessionSignature($login, $hash)
		{
			return hash('sha256', $login.' '.$hash);
		}

		//Имена переменных сессии: [session] login / signature
		private function sessionVar($what)
		{
			return $this->config['session'][$what] ?? $what;
		}

		//Стартует сессию с учётом настроек [session]
		private function ensureSession()
		{
			if (session_status() !== PHP_SESSION_NONE) return;

			if ($name = $this->config['session']['name'] ?? null)
				session_name($name);

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
			if (! $this->policy->password($user['password'])) return false;

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
				if (! $this->policy->password($user['password'])) return false;

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

			$ip = $this->policy->ip();
			$this->policy->purge();

			//[policy]: маска → бан адреса → лок пары
			if (! $this->policy->allowIp($ip))     return false;
			if ($this->policy->banned($ip))        return false;
			if ($this->policy->locked($ip, $login)) return false;

			$user = $this->get($login);
			if (! $user)           { $this->policy->fail($ip, $login); return false; }
			if ($user['disable'])  { $this->policy->fail($ip, $login); return false; }

			$verify = $this->verify($password, $user['hash']);
			if ($verify === false) { $this->policy->fail($ip, $login); return false; }

			//Успех — сброс счётчика пары
			$this->policy->reset($ip, $login);

			//Прозрачная миграция легаси md5-хэша на password_hash
			if ($verify === 'legacy')
				$user['hash'] = $this->rehash($login, $password);

			$this->ensureSession();

			//Защита от session fixation: новый id при повышении привилегий
			if (($this->config['session']['regenerate-id'] ?? true) and session_status() === PHP_SESSION_ACTIVE)
				session_regenerate_id(true);

			$_SESSION[$this->sessionVar('login')]     = $login;
			$_SESSION[$this->sessionVar('signature')] = $this->sessionSignature($user['login'], $user['hash']);
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

			$sl = $this->sessionVar('login');
			$ss = $this->sessionVar('signature');

			if (!isset($_SESSION[$sl]) or (!isset($_SESSION[$ss]))) return false;

			$user = $this->get($_SESSION[$sl]);
			if (! $user) return false;

			if (! hash_equals($this->sessionSignature($user['login'], $user['hash']), $_SESSION[$ss])) return false;

			return $this->loggedCache = $user;
		}

		public function logout()
		{
			$this->loggedCache = null;
			unset($_SESSION[$this->sessionVar('login')]);
			unset($_SESSION[$this->sessionVar('signature')]);
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

			//Пути могли быть сохранены с '//' (listing принимал 'app/controllers//admin')
			//— нормализуем обе стороны при сопоставлении
			foreach ((array) $user['denied'] as $rule => $val)
				if (str_replace('//', '/', $rule) === str_replace('//', '/', (string) $access_item))
					return $val;
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
			foreach (array_keys((array) dba_handlers(true)) as $h)
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
	#                  ПОЛИТИКА ДОСТУПА                                #
	# ---------------------------------------------------------------- #

	/*
	 * Политика безопасности: счётчики промахов, локи пар (ip,login),
	 * баны адресов и правила паролей — секция [policy] user.ini.
	 *
	 * Таблица policy: ключ (ip,login); пустая часть ключа — wildcard:
	 *   (ip,'')   — маркер бана адреса
	 *   ('',login)— глобальный счётчик логина (зарезервировано)
	 *   until     — конец лока/бана (UTC); NULL = нет блока,
	 *               '9999-12-31' — перманентный бан
	 */
	class UserPolicy
	{
		private $pdo;
		private $config;

		public function __construct($pdo, $config=[])
		{
			$this->pdo    = $pdo;
			$this->config = $config;

			$this->pdo->exec(
				"CREATE TABLE IF NOT EXISTS 'policy' (
					'ip'      TEXT NOT NULL,
					'login'   TEXT NOT NULL,
					'count'   INTEGER DEFAULT 0,
					'until'   TEXT,
					'updated' TEXT,
					PRIMARY KEY (ip, login)
				)"
			);
		}

		/*
		 * ip() — ip адрес клиента
		 *
		 * Берём только REMOTE_ADDR. X-Forwarded-For и прочие заголовки
		 * прокси намеренно игнорируются: их подставляет сам клиент, а значит
		 * атакующий мог бы «переобуваться» в новые адреса и обнулять счётчики.
		 *
		 * @return string  адрес ('' в CLI/без окружения)
		 */
		public function ip()
		{
			return (string) ($_SERVER['REMOTE_ADDR'] ?? '');
		}

		/*
		 * allowIp($ip) — пропускает ли адрес маска ip.allow
		 *
		 * Маска — glob (как у fnmatch: '127.*', '10.0.?.?', '*'), строка
		 * или json-массив масок. Совпадение любой — пропускаем.
		 * Пустой список/'*' — разрешены все. Метод — только фильтр доступа,
		 * в счётчики промахов отказ здесь не пишется.
		 *
		 * @param  string $ip
		 * @return bool
		 */
		public function allowIp($ip)
		{
			foreach ((array) ($this->config['ip']['allow'] ?? '*') as $mask)
				if (fnmatch(trim($mask), $ip)) return true;
			return false;
		}

		/*
		 * locked($ip, $login) — заблокирована ли пара ip+login
		 *
		 * Лок — это строка (ip,login) с until в будущем (UTC).
		 * Блокируется именно ПАРА, а не пользователь целиком: атакующий,
		 * подбирающий чужой пароль, замораживает только «свой вход» —
		 * легитимный пользователь с другого адреса ходит свободно.
		 *
		 * @param  string $ip
		 * @param  string $login
		 * @return bool     true — пара под блокировкой, вход отклонять
		 */
		public function locked($ip, $login)
		{
			$stmt = $this->pdo->prepare("SELECT until FROM 'policy' WHERE ip = ? AND login = ?");
			$stmt->execute([$ip, $login]);
			$until = $stmt->fetchColumn();
			return $until !== false && $until !== null && strtotime($until.' UTC') > time();
		}

		/*
		 * banned($ip) — забанен ли адрес (bruteforce-защита)
		 *
		 * Два способа оказаться в бане:
		 *  1. Живой маркер — строка (ip,'') с until в будущем
		 *     ('9999-12-31' = перманентный бан).
		 *  2. Суммарное число промахов адреса (SUM count по всем его
		 *     логинам) достигло порога ip.ban — тогда маркер ставится
		 *     прямо здесь (побочный эффект: ПИШЕТ в таблицу).
		 *
		 * Срок бана — ip.bantime минут; 0 = навсегда.
		 * Истёкший маркер трактуется как «бан снят» и возвращает false.
		 *
		 * @param  string $ip
		 * @return bool     true — вход с адреса закрыт
		 */
		public function banned($ip)
		{
			$stmt = $this->pdo->prepare("SELECT until FROM 'policy' WHERE ip = ? AND login = ''");
			$stmt->execute([$ip]);
			$until = $stmt->fetchColumn();
			if ($until !== false) return strtotime($until.' UTC') > time();

			$ban = (int) ($this->config['ip']['ban'] ?? 0);
			if ($ban <= 0) return false;

			$stmt = $this->pdo->prepare("SELECT COALESCE(SUM(count),0) FROM 'policy' WHERE ip = ? AND login != ''");
			$stmt->execute([$ip]);
			if ((int) $stmt->fetchColumn() < $ban) return false;

			//Порог добран — маркер бана. bantime=0 → перманент (сентинел-дата)
			$minutes = (int) ($this->config['ip']['bantime'] ?? 0);
			$until = $minutes > 0 ? gmdate('Y-m-d H:i:s', time() + $minutes*60) : '9999-12-31';
			$this->pdo->prepare("INSERT OR REPLACE INTO 'policy' (ip, login, count, until, updated)
								VALUES (?, '', 0, ?, datetime('now'))")->execute([$ip, $until]);
			return true;
		}

		/*
		 * fail($ip, $login) — регистрирует неудачную попытку входа
		 *
		 * Делает две вещи:
		 *  1. Инкрементит счётчик пары (ip,login) — upsert через
		 *     INSERT OR IGNORE + UPDATE (SQLite <3.24, без ON CONFLICT).
		 *  2. Когда count доходит до lock.attempts — ставит паре лок:
		 *     until = now + lock.minutes.
		 *
		 * Пустые части ключа не пишем — они зарезервированы под
		 * wildcard-сущности ((ip,'') = бан адреса, ('',login) = глобальный
		 * счётчик логина).
		 *
		 * ВАЖНО: until, однажды поставленный, метод больше не двигает —
		 * иначе постоянные попытки атакующего сдвигали бы окно блока
		 * вперёд бесконечно (count при этом копить можно, он не вредит).
		 *
		 * @param string $ip
		 * @param string $login
		 */
		public function fail($ip, $login)
		{
			//Wildcard-части ключа резервированы системой — пустых из запроса быть не должно
			if ($ip === '' || $login === '') return;

			$this->pdo->prepare("INSERT OR IGNORE INTO 'policy' (ip, login, count, updated)
								VALUES (?, ?, 0, datetime('now'))")->execute([$ip, $login]);
			$this->pdo->prepare("UPDATE 'policy' SET count = count + 1, updated = datetime('now')
								WHERE ip = ? AND login = ?")->execute([$ip, $login]);

			$att = (int) ($this->config['lock']['attempts'] ?? 0);
			$min = (int) ($this->config['lock']['minutes'] ?? 0);
			if ($att <= 0 || $min <= 0) return;

			$stmt = $this->pdo->prepare("SELECT count FROM 'policy' WHERE ip = ? AND login = ? AND until IS NULL");
			$stmt->execute([$ip, $login]);
			if ((int) $stmt->fetchColumn() < $att) return;

			$until = gmdate('Y-m-d H:i:s', time() + $min*60);
			$this->pdo->prepare("UPDATE 'policy' SET until = ? WHERE ip = ? AND login = ?")
				->execute([$until, $ip, $login]);
		}

		/*
		 * reset($ip, $login) — сброс счётчика пары после успешного входа
		 *
		 * Удаляет строку (ip,login) целиком — и промахи, и лок, если был.
		 * Намеренно НЕ трогает остальные строки адреса: суммарный
		 * счётчик ip.ban должен копить промахи атакующего — иначе он бы
		 * отмывал репутацию адреса, логинясь между сериями в свой аккаунт.
		 *
		 * @param string $ip
		 * @param string $login
		 */
		public function reset($ip, $login)
		{
			$this->pdo->prepare("DELETE FROM 'policy' WHERE ip = ? AND login = ?")
				->execute([$ip, $login]);
		}

		/*
		 * list() — все строки policy (диагностика/консоль)
		 *
		 * @return array строки [ip, login, count, until, updated]
		 */
		public function list()
		{
			return $this->pdo->query("SELECT * FROM 'policy' ORDER BY ip, login")
				->fetchAll(\PDO::FETCH_ASSOC);
		}

		/*
		 * unlock($login, $ip=null) — снять лок/счётчик пары
		 *
		 * С ip — конкретная пара; без — все записи логина (wildcard-строки
		 * (ip,'') и ('',login) не затрагиваются — это не «пары»).
		 * Строки удаляются целиком: и лок, и накопленный count.
		 */
		public function unlock($login, $ip=null)
		{
			if ($ip === null)
				$this->pdo->prepare("DELETE FROM 'policy' WHERE login = ? AND ip != ''")
					->execute([$login]);
			else
				$this->pdo->prepare("DELETE FROM 'policy' WHERE ip = ? AND login = ?")
					->execute([$ip, $login]);
		}

		/*
		 * unban($ip) — снять бан адреса
		 *
		 * Удаляет маркер (ip,'') И все пары адреса: если оставить
		 * накопленный count, banned() по SUM пересоздаст бан на
		 * следующем же запросе — «чистый лист» единственный полезный.
		 */
		public function unban($ip)
		{
			$this->pdo->prepare("DELETE FROM 'policy' WHERE ip = ?")->execute([$ip]);
		}

		/*
		 * purge() — чистка протухших записей
		 *
		 * Удаляет строки, которые одновременно:
		 *  - старше суток по updated (заброшенные счётчики, мусор атак),
		 *  - НЕ имеют живого until (активные локи и баны трогать нельзя —
		 *    удаление равносильно досрочной разблокировке).
		 * Перманентные баны ('9999-12-31') условие until<=now не проходят
		 * и живут вечно — как и задумано.
		 *
		 * Вызывается из login(): логины редки, DELETE по PK дёшев,
		 * отдельного крона не требуется.
		 */
		public function purge()
		{
			$this->pdo->exec("DELETE FROM 'policy'
				WHERE (until IS NULL OR until <= datetime('now'))
				AND updated < datetime('now', '-1 day')");
		}

		/*
		 * password($password) — пароль соответствует политике?
		 *
		 * Проверяет правила из [policy] password.*:
		 *  - password.length  — минимальная длина (mb_strlen)
		 *  - password.classes — обязательные классы символов, массив:
		 *      'lower'  — строчные буквы (вкл. кириллицу)
		 *      'upper'  — прописные буквы
		 *      'digit'  — цифры
		 *      'symbol' — знаки (всё, что не буква и не цифра)
		 * Пустые правила = политика выключена, принимаем всё.
		 *
		 * Применяется при установке пароля (add/edit), на вход не влияет —
		 * старые слабые пароли продолжают работать до смены.
		 *
		 * @param  string $password
		 * @return bool
		 */
		public function password($password)
		{
			$p = (array) ($this->config['password'] ?? []);

			if (mb_strlen($password) < (int) ($p['length'] ?? 0)) return false;

			foreach ((array) ($p['classes'] ?? []) as $class)
			{
				switch ($class)
				{
					case 'lower':  if (!preg_match('/[a-zа-яё]/u', $password))     return false; break;
					case 'upper':  if (!preg_match('/[A-ZА-ЯЁ]/u', $password))     return false; break;
					case 'digit':  if (!preg_match('/\d/', $password))             return false; break;
					case 'symbol': if (!preg_match('/[^\p{L}\p{N}]/u', $password)) return false; break;
				}
			}

			return true;
		}
	}


	# ---------------------------------------------------------------- #
	#                  РОЛИ (наборы прав доступа)                      #
	# ---------------------------------------------------------------- #
	class UserRoles
	{
		private $pdo;
		private $table;

		public function __construct($pdo, $table='roles')
		{
			$this->pdo   = $pdo;
			$this->table = $table;
		}

		//$APP->user->role->get()          - все роли (name => rules)
		//$APP->user->role->get($name)     - правила одной роли
		function get($name=null)
		{
			if ($name !== null)
			{
				$stmt = $this->pdo->prepare("SELECT rules FROM '{$this->table}' WHERE name = ?");
				$stmt->execute([$name]);
				$rules = $stmt->fetchColumn();
				return $rules === false ? null : json_decode($rules, true);
			}

			$roles = [];
			foreach ($this->pdo->query("SELECT name, rules FROM '{$this->table}'") as $row)
				$roles[$row['name']] = json_decode($row['rules'], true);
			return $roles;
		}

		//$APP->user->role->set($name, $rules)
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
