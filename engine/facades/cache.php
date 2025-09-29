<?php

	/*
	 * Интерфейс кеширования через Memcached
	 *
	 * Реализовано согласованное хеширование между отказавшими серверами.
	 * Материал: https://github.com/php-memcached-dev/php-memcached/issues/154
	 *
	 */

	namespace App\Facade;

	# ---------------------------------------------------------------- #
	#                  ОПИСАНИЕ     ИНТЕРФЕЙСА                         #
	# ---------------------------------------------------------------- #
	interface QCacheInterface
	{
		// Получение данных из кеша по ключу
		public function get($key);

		// Устанловить данные $value по ключу $key на время жизни $lifetime
		public function set($key, $value, $lifetime=null);

		// Удаление ключа
		public function delete($key);
	}


	# ---------------------------------------------------------------- #
	#                 РЕАЛИЗАЦИЯ   ИНТЕРФЕЙСА                          #
	# ---------------------------------------------------------------- #
	class QMemcachedInterface implements QCacheInterface
	{


		public $config;
		public $mem_interface;


		function __construct($config)
		{
			$this->config = $config;

			//Проверим на возможность создания memcached
			if (! class_exists('Memcached'))
			{
				trigger_error("Memcached module for php is not installed!");
				return $this;
			}

			//Создадим экземпляр. если его нет
			if (!$this->mem_interface)
				$this->mem_interface = new \Memcached($config['pool']);

			//Забьем требуемые параметры
			$this->mem_interface->setOption(\Memcached::OPT_CONNECT_TIMEOUT, 10);
			$this->mem_interface->setOption(\Memcached::OPT_BINARY_PROTOCOL,true);
			$this->mem_interface->setOption(\Memcached::OPT_REMOVE_FAILED_SERVERS, true);
			$this->mem_interface->setOption(\Memcached::OPT_DISTRIBUTION, \Memcached::DISTRIBUTION_CONSISTENT);
			$this->mem_interface->setOption(\Memcached::OPT_LIBKETAMA_COMPATIBLE,true);


			//~ foreach ($config['options'] as $_name => $_value)
			//~ {
				//~ $this->mem_interface->setOption(Memcached::$_name, $_value);
			//~ }

			//Добавление серверов в пул
			$server_list = $this->mem_interface->getServerList();
			if (!count($server_list))
			{
				foreach ($config['servers'] as $_server)
				{
					$this->mem_interface->addServer($_server['host'], $_server['port']);
				}
			}


		}

		/*
		 *
		 * name: set memcached
		 * @param $key = Ключ, значение, время жизни в секундах
		 * @return
		 *
		 */
		public function set($key, $value, $lifetime=null)
		{
			//Убедимся, что доступен memcached
			if (!$this->mem_interface) return false;

			//Ищем возможность записать на доступных серверах
			foreach ($this->config['servers'] as $current)
			{
				$result = $lifetime ? $this->mem_interface->set(md5($key), $value, time()+$lifetime) : $this->mem_interface->set( md5($key), $value );

				//Убеждаемся,что запись в кеш прошла без ошибок
				if ( $this->mem_interface->getResultCode() == 0 ) return $result;

				//Это необходимо. Если один или несколько серверов вышли из строя - ребаланс
				$this->mem_interface->setOption(\Memcached::OPT_LIBKETAMA_COMPATIBLE, true);
			}

			return false;
		}


		/*
		 *
		 * name: get
		 * @param $key
		 * @return
		 *
		 */
		public function get($key)
		{
			if (!$this->mem_interface) return false;

			//Ищем ключ на доступных серверах
			foreach ($this->config['servers'] as $current)
			{
				$result = $this->mem_interface->get( md5($key) );

				//Убеждаемся, что чтение произошло с работающего сервера
				if ( $this->mem_interface->getResultCode() == 0 ) return $result;

				//Это необходимо. Если один или несколько серверов вышли из строя - ребаланс
				$this->mem_interface->setOption(\Memcached::OPT_LIBKETAMA_COMPATIBLE, true);
			}

			return false;
		}

		/*
		 *
		 * name: delete key
		 * @param $key
		 * @return
		 *
		 */
		public function delete($key)
		{
			if (!$this->mem_interface) return false;

			//Ищем ключ на доступных серверах
			foreach ($this->config['servers'] as $current)
			{
				$result = $this->mem_interface->delete( md5($key) );

				//Убеждаемся, что все прошло без ошибок
				if ( $this->mem_interface->getResultCode() == 0 ) return $result;

				//Это необходимо. Если один или несколько серверов вышли из строя - ребаланс
				$this->mem_interface->setOption(\Memcached::OPT_LIBKETAMA_COMPATIBLE, true);
			}

			return false;
		}
	}


	/**
	 * Класс отвечает за процес предоставления интерфейса кеширования через файлы
	 */
	class QFileCacheInterface
	{

		public $config;

		public function __construct($config)
		{
			$this->config = $config;
		}

		function get($key)
		{
			if (! file_exists(file_get_contents( $this->config['files']['folder'].'/'.md5($key)))) return null;
			return file_get_contents( $this->config['files']['folder'].'/'.md5($key) );
		}

		function set($key, $value, $lifetime=null)
		{
			file_put_contents($this->config['files']['folder'].'/'.md5($key), $value, LOCK_EX);
		}

		function delete($key)
		{
			if (! file_exists(file_get_contents( $this->config['files']['folder'].'/'.md5($key)))) unlink($this->config['files']['folder'].'/'.md5($key));
		}

		function file()
		{
			return $this;
		}

	}







	/**
	 * Класс отвечает за интеграцию всех интерфейсов в платформу
	 */
	class Cache implements QCacheInterface
	{
		public $config;
		public $memcached;
		public $filecache;

		public function __construct($config)
		{
			//Загрузим себе конфиг
			$this->config = $config;

			//Поключим внешние интерфейсы кеширования
			$this->filecache = new QFileCacheInterface($config['filecache']);
			$this->memcached = new QMemcachedInterface($config['memcached']);
		}

		public function get($key)
		{
			//Берем из конфига провайдера кеширования по умолчанию
			$default_provider = $this->config['default'];
			//Выполняем запрос
			return $this->$default_provider->get($key);
		}

		public function set($key, $value, $lifetime=null)
		{
			//Берем из конфига провайдера кеширования по умолчанию
			$default_provider = $this->config['default'];
			//Выполняем запрос
			return $this->$default_provider->set($key, $value, $lifetime);
		}

		public function delete($key)
		{
			//Берем из конфига провайдера кеширования по умолчанию
			$default_provider = $this->config['default'];
			//Выполняем запрос
			return $this->$default_provider->delete($key);
		}
	}


	# ---------------------------------------------------------------- #
	# --------------[ СОЗДАЕМ И ПОДКЛЮЧАЕМ ИНТЕРФЕЙС ]---------------- #
	# ---------------------------------------------------------------- #

	return new Cache( $this->config->get(__file__) );
