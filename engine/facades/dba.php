<?php


	//~ lib('db_adapter/dba.php');
	//~
	//~ class berkley_adapter
	//~ {
		//~ //Конфигурациооный файл
		//~ public $config;
		//~
		//~ //Указатели на открытые базы данных
		//~ protected $HDB = array();
		//~
		//~ public function __construct($mode='c', $hadle='db4')
		//~ {
			//~ $this->config['folder'] = '';
			//~ $this->config['mode'] 	= $mode;
			//~ $this->config['hadle'] 	= $hadle;
		//~ }
		//~
		//~ public function db($db)
		//~ {
			//~ //Если файл базы данных не открыт - создаем соединение
			//~ if (! isset($this->HDB[$db]) )
			//~ {
				//~ $filename = '';
				//~
				//~ //Если путь указан, то поставим вконце него косую, что бы отделить путь от названия файла
				//~ if ($this->config['folder'] != '') $filename = $this->config['folder'].'/';
				//~
				//~ $filename = $filename . $db;
				//~ $this->HDB[$db] = new db_adapter_dba( $filename, $this->config['mode'], $this->config['hadle']);
			//~ }
			//~
			//~ //Возвращаем указатель
			//~ return $this->HDB[$db];
		//~ }
		//~
		//~
		//~
		//~ //Проверка на существование базы
		//~ public function db_exists($db)
		//~ {
			//~ $filename = '';
			//~
			//~ //Если путь указан, то поставим вконце него косую, что бы отделить путь от названия файла
			//~ if ($this->config['folder'] != '') $filename = $this->config['folder'].'/';
		//~
						//~
			//~ //Строим имя файла
			//~ $filename = $filename . $db;
//~
			//~ //Возвращаем результат наличия базы
			//~ return file_exists($filename);
		//~ }
		//~
	//~ }
	//~





	class Berkley_Adapter2
	{
		//Конфигурациооный файл
		public $config;

		//Имя базы данных
		protected $DB = '';

		public function __construct($mode='c', $hadle='db4')
		{
			$this->config['folder'] = '';
			$this->config['mode'] 	= $mode;
			$this->config['hadle'] 	= $hadle;
		}


		protected function open()
		{
			//Если путь до базы не определен
			if ($this->DB == '')  return false;
			//Открываем соединение с базой
			return dba_open($this->DB, $this->config['mode'], $this->config['hadle']);
		}

		protected function close($DBH)
		{
			//Закрываем соединение с базой
			return dba_close($DBH);
		}


		//Проверка на существование базы
		public function db_exists($db)
		{
			$filename = '';

			//Если путь указан, то поставим вконце него косую, что бы отделить путь от названия файла
			if ($this->config['folder'] != '') $filename = $this->config['folder'].'/';


			//Строим имя файла
			$filename = $filename . $db;

			//Возвращаем результат наличия базы
			return file_exists($filename);
		}


		//Указывает, с какой базой данных работать
		public function db($db)
		{
			$filename = '';

			//Если путь указан, то поставим вконце него косую, что бы отделить путь от названия файла
			if ($this->config['folder'] != '') $filename = $this->config['folder'].'/';

			$filename = $filename . $db;

			//Путь до базы не определен
			if ( $filename == '') return false;

			//Запоминаем путь к базе
			$this->DB = $filename;

			//Возвращаем текущий экземпляр класса
			return $this;
		}

		//Вернет имена всех ключей базы
		public function keys()
		{
			//Открываем соединение с базой
			$DBH = $this->open();

			//Получаем первый ключ базы (необходим для начала прохода по всем записям коллекции)
			$key = dba_firstkey($DBH);
			$result = array();
			//До тех пор, пока ключ не станет равнятся false вычитываем остальные ключи
			while ($key !== false)
			{
			    //добавляем ключ
				$result[] = $key;

				//Смотрим следующий ключ
				$key = dba_nextkey($DBH);
			}
			//Закрываем соединение с базой
			$this->close($DBH);

			return (array) $result;
		}

		//Вернет все записи
		public function all()
		{
			//Открываем соединение с базой
			$DBH = $this->open();
			if (!$DBH) return false;
			//Получаем первый ключ базы (необходим для начала прохода по всем записям коллекции)
			$key = dba_firstkey($DBH);

			$result = array();
			//До тех пор, пока ключ не станет равнятся false вычитываем остальные ключи со значениями
			while ($key !== false)
			{
			    //добавляем ключ
				$result[$key] = unserialize(dba_fetch($key, $DBH));

				//Смотрим следующий ключ
				$key = dba_nextkey($DBH);
			}

			//Закрываем соединение с базой
			$this->close($DBH);

			return (array) $result;
		}

		function insert($key, $var)
		{
			//Открываем соединение с базой
			$DBH = $this->open();
			//Вставляем значение
			$result = dba_insert($key, serialize($var), $DBH);
			//Закрываем соединение с базой
			$this->close($DBH);
			return $result;
		}

		function update($key, $var)
		{
			//Открываем соединение с базой
			$DBH = $this->open();
			$result = dba_replace($key, serialize($var), $DBH);
			//Закрываем соединение с базой
			$this->close($DBH);
			return $result;
		}

		function select($key)
		{
			//Открываем соединение с базой
			$DBH = $this->open();
			$result = dba_fetch($key, $DBH);
			if ($result) $result = unserialize($result);
			//Закрываем соединение с базой
			$this->close($DBH);
			return $result;
		}

		function delete($key)
		{
			//Открываем соединение с базой
			$DBH = $this->open();
			$result = dba_delete($key, $DBH);
			//Закрываем соединение с базой
			$this->close($DBH);
			return $result;
		}

		function exists($key)
		{
			//Открываем соединение с базой
			$DBH = $this->open();
			$result = dba_exists($key, $DBH);
			//Закрываем соединение с базой
			$this->close($DBH);
			return $result;
		}

		function optimize()
		{
			//Открываем соединение с базой
			$DBH = $this->open();
			$result = dba_optimize($DBH);
			//Закрываем соединение с базой
			$this->close($DBH);
			return $result;
		}

	}












/*
==================================================================================================================================
			Подключим модуль к платформе
==================================================================================================================================
*/

	$dba = new berkley_adapter2();
	$config = $this->config->get(__file__);
	if ($config) $dba->config = $config;
	return $dba;

