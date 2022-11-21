<?php

	/*
	* TODO - DEPRICATED - УСТАРЕЛО
==================================================================================================================================
			Краткий мануал по использованию модуля.
			В подключенном состоянии обратиться к модулю можно по адресу $app->object;
==================================================================================================================================

	//Получить объект "rec"
	$app->object->get('rec');

	//Записать в объект "rec" значение переменной $value
	$app->object->set('rec', $value);

	//Удалить объект с имененем "rec"
	$app->object->del('rec');

	//Получить объект "main" из коллекции "menu"
	$app->object->collection('menu')->get('main');

	//записать объект "main" со значением переменной $value в коллекцию "menu"
	$app->object->collection('menu')->set('main', $value);

	//Получить все объекты из коллекции "menu"
	$app->object->collection('menu')->all();

	//Получить все объекты, для которых коллекция не указана
	$app->object->collection()->all();

	//Удалить объект с имененем "rec" из коллекции "menu"
	$app->object->collection('menu')->del('rec');

	//Удалить всю коллекцию "menu"
	$app->object->collection('menu')->drop();

	//Получить список всех коллекций
	$app->object->collection_list();


	*/

	namespace unit\object;

	class object_provider_dba
	{
		//Хранит параметры конфигурации.
		public 	$config = array();

		//переменная хранит значение текущей коллекции
		private $current_collection;

		//Интерфейс к менеджеру базы berkley
		private $dba_interface;



		public function __construct($dba_unit)
		{
			//Устанавливаем значения опций по умолчанию
			$this->config['folder']	= '';
			$this->config['ext']	= 'obj';

			//Подключаем интерфейс к dba
			$this->dba_interface = clone $dba_unit;

			//Аннулируем папку интерфейса dba, что бы исключить наложение путей в конфигах
			$this->dba_interface->config['folder'] = '';
		}


		//Устанавливает коллекцию для дальнейшей работы с ней
		public function collection($collection_name)
		{
			$this->current_collection = base64_encode($collection_name);
			return $this;
		}


		//Возвращает список всех коллекций
		public function collection_list()
		{
			if ($this->config['folder'] != '') $folder = $this->config['folder'].'/';

			//Создаем маску выборки
			$mask = $folder.'*.'.$this->config['ext'];

			//Проходим по всем коллекциям объектов в папке
			foreach (glob($mask) as $filename)
			{

				//Получаем информацию о файле
				$tmp = pathinfo($filename);

				//Добавляем в список коллекций
				$result[] = base64_decode($tmp['filename']);
			}

			//Возвращаем полученный массив
			return (array) $result;
		}



		//Записываем объект
		public function set($name, $value)
		{
			//Получаем имя базы, в которой храним коллекцию
			$filename = $this->db_filename();
			//Добавляем объект в базу
			return $this->dba_interface->db($filename)->update($name, $value);

			//~ //Если объекта нет....
			//~ if (! $this->dba_interface->db($filename)->exists($name) )
			//~ {
				//~ //...оздаем новый
				//~ return $this->dba_interface->db($filename)->insert($name, $value);
			//~ }
			//~ else
			//~ {
				//~ //...иначе - обновляем существующий
				//~ return $this->dba_interface->db($filename)->update($name, $value);
			//~ }
		}


		/*
		 * name: Получаем объект
		 * @param	имя объекта
		 * @return
		 */
		public function get($name)
		{
			//Получаем имя базы, в которой храним коллекцию
			$filename = $this->db_filename();

			//Если файла с коллекцие не существует - даже нет смысла выполнять код дальше - просто вернем null
			if (! $this->dba_interface->db_exists($filename)) return null;

			//Запрашиваем объект
			return $this->dba_interface->db($filename)->select($name);
		}



		/*
		 * name: Удаляем объект
		 * @param	имя объекта
		 * @return
		 */
		public function del($name)
		{
			//Получаем имя базы, в которой храним коллекцию
			$filename = $this->db_filename();

			//Если файла с коллекцие не существует - даже нет смысла выполнять код дальше - просто вернем null
			if (! $this->dba_interface->db_exists($filename))  return null;

			//Удаляем объект
			return $this->dba_interface->db($filename)->delete($name);
		}


		/*
		 * name: Создаем коллекцию
		 * @param	имя объекта
		 * @return
		 */
		public function create()
		{
			//Получаем имя базы, в которой храним коллекцию
			$filename = $this->db_filename();

			//Если коллекция уже существует - просто вернем положительный исход
			if ($this->dba_interface->db_exists($filename))  return true;
			return $this->dba_interface->db($filename);
		}

		/*
		 * name: Удаляем коллекцию
		 * @param
		 * @return
		 */
		public function drop()
		{
			//Получаем имя базы, в которой храним коллекцию
			$filename = $this->db_filename();

			//Если файла с коллекцие не существует - даже нет смысла выполнять код дальше - просто вернем null
			if (! $this->dba_interface->db_exists($filename))  return null;

			//попросили удалить всю коллекцию
			return unlink($filename);
		}



		/*
		 * name: Получить все имена коллекции
		 * @param	имя объекта
		 * @return
		 */
		public function names()
		{
			//Получаем имя базы, в которой храним коллекцию
			$filename = $this->db_filename();

			//Если базы с коллекцией не существует - даже нет смысла выполнять код дальше - просто вернем пустой массив
			if (! $this->dba_interface->db_exists($filename)) return array();

			//Просим базу вернуть значения всех ключей
			return	$this->dba_interface->db($filename)->keys();

			//~ //Получаем первый ключ базы (необходим для начала прохода по всем записям коллекции)
			//~ $key = $this->dba_interface->db($filename)->firstkey();
			//~
			//~ while ($key !== false)
			//~ {
			    //~ //добавляем ключ
				//~ $result[] = $key;
				//~
				//~ //Смотрим следующий ключ
				//~ $key = $this->dba_interface->db($filename)->nextkey();
			//~ }
			//~
			//~ return (array) $result;
		}



		/*
		 * name: Получить все записи коллекции
		 * @param	имя объекта
		 * @return
		 */
		public function all()
		{
			//Получаем имя базы, в которой храним коллекцию
			$filename = $this->db_filename();
			//Просим базу вернуть все значнения
			return	$this->dba_interface->db($filename)->all();


			//~ $name_list = $this->names();

			//~ foreach ($name_list as $key)
			//~ {
				//~ //Подгружаем объект
				//~ $result[$key] = $this->dba_interface->db($filename)->select($key);
			//~ }
			//~
			//~ return (array) $result;


			/*
				Ветка прежнего мертвого кода. Оставлена для ознакомления
			*/


			//Получаем имя базы, в которой храним коллекцию
			$filename = $this->db_filename();

			//Если базы с коллекцие не существует - даже нет смысла выполнять код дальше - просто вернем пустой массив
			if (! $this->dba_interface->db_exists($filename)) return array();

			//Получаем первый ключ базы (необходим для начала прохода по все записям коллекции)
			$key = $this->dba_interface->db($filename)->firstkey();

			while ($key != false)
			{
			    //Подгружаем объект
				$result[$key] = $this->dba_interface->db($filename)->select($key);

				//Смотрим следующий ключ
				$key = $this->dba_interface->db($filename)->nextkey();
			}

			return (array) $result;
		}

		//Экспортировать все объекты в строку
		public function export()
		{
			//~ $collections = $this->collection_list();
			foreach ($this->collection_list() as $colindex => $collection)
			{
				$result[$collection] = $this->collection($collection)->all();
			}

			return json_encode($result);
		}

		//Собирает путь до базы, в которой хранится коллекция
		private function db_filename()
		{
			return $this->config['folder']. '/' .$this->current_collection. '.' .$this->config['ext'];
		}

	}








/*
==================================================================================================================================
			Подключим модуль к платформе
==================================================================================================================================
*/


	//Создадим экземпляр и передадим ему модуль для работы с berkley dba
	$op = new object_provider_dba($this->dba);


	$config = $this->config->get(__file__);


	//Подгружаем конфигурацию
	if ($config)
	{
		$op->config = $config;
	}
	else
	{
		//Если конфигурационного файла нет - создаем
		$op->config['folder'] = 'engine/database/objects';

		$this->config->set($op->config, __file__);
	}


	//Возвращаем интерфейс управления провайдером
	return $op;



