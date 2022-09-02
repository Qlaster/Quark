<?php
	namespace unit\qblog;

	lib('db_adapter/dbal.php');

/*
	


	$blog->post->insert();
	$blog->post->delete();
	$blog->post->update();
	$blog->post->all();
	
	$blog->post->comments();
	$blog->post->comments->;
	
	

	//Добавить пост
	$blog->post()->insert($post);
	
	
	
	//Удалить пост
	$blog->post('1')->delete();
	
	//Редактировать пост
	$blog->post('1')->update($post);
	
	//Добавить комментарий к посту
	$blog->post('1')->comment('')->insert($comment);
	
	//Удалить комментарий к посту
	$blog->post('1')->comment('1')->Delete();
	//Очистить все комментарии к посту
	$blog->post('1')->comment('')->Delete();
	
	//Показать список всех постов (инверсия по времени, пагенация)
	$blog->post('1')->All(1,10);
	
	//Показать список всех комментариев к посту (инверсия по времени, пагенация)
	$blog->post('1')->Comment('')->All(1,10);
	
	*/
	//Поиск по find
	
	

	class quick_blog
	{
		public $post;
		public $comments;
		
		private $visits;
				
				
		
		public function __construct($ORM_PDO)
		{
			//Конструируем таблицы
			$ORM_PDO->SQL('CREATE TABLE IF NOT EXISTS posts (id INTEGER PRIMARY KEY AUTOINCREMENT, name, text, find, logo, tags, date, author, label, unixtime);');
			$ORM_PDO->SQL('CREATE TABLE IF NOT EXISTS comments (id INTEGER PRIMARY KEY AUTOINCREMENT, post, text, find, date, logo, author, label, unixtime);');
			$ORM_PDO->SQL('CREATE TABLE IF NOT EXISTS visits (id INTEGER PRIMARY KEY AUTOINCREMENT, login, unixtime);');
			
			//Создаем интерфейс для постов
			$this->post = new quick_blog_db_editor($ORM_PDO, 'posts');
			
			//Создаем интерфейс для комментариев
			$this->comments = new quick_blog_db_editor($ORM_PDO, 'comments');
			
			//Создаем интерфейс для визитов
			$this->visits = $ORM_PDO;
		}
		
		
		
		
		public function Post($id_post=null)
		{
			$this->post->select = $id_post;
			$this->comments->select = $id_post;
			return $this->post;
		}
		
		
		
		
		public function Comments($id_post=null)
		{
			//~ $this->post->select = $id_post;
			$this->comments->select = $id_post;
			return $this->comments;
		}
		
		public function Clearfix()
		{
			$posts = (array) $this->post->count();
			$posts = array_keys($post);
			
			
		}
		
		public function visits_set($login)
		{
			$record['login']	 = $login;
			$record['unixtime']	 = time();
				
			$this->visits->table('visits')->BeginTransaction();
				$this->visits->table('visits')->where('login = ?', $login)->delete();
				$this->visits->table('visits')->Insert($record);
			$this->visits->table('visits')->Commit();
		}
		
		public function visits_get($login)
		{			
			return (int) $this->visits->table('visits')->where('login = ?', $login)->select('*')[0]['unixtime'];			
		}
	}
	
	
	
	class quick_blog_db_editor	
	{
		private $table_name;
		public $ORM_PDO;
		public $select;
		
		public function __construct($ORM_PDO, $table_name)
		{
			$this->table_name 	= $table_name;
			$this->ORM_PDO 		= $ORM_PDO;
		}
		
		
		public function add($data)
		{
			//Если нам не передали дату публикации - то берем текущую
			if (! isset($data['date']) ) $data['date'] = date('d.m.Y H:i');
			$data['unixtime'] = time();
			$data['text'] = trim($data['text']);
			$this->ORM_PDO->table($this->table_name)->insert($data);
			return $this;
		}
		
		public function delete($id)
		{
			//Если нам передали целую запись, то вытягиваем из нее идентификатор
			if ( isset($id['id'])) $id = $id['id'];
			//Отсылаем запрос на исполнение
			$this->ORM_PDO->table($this->table_name)->where('id = ?', $id)->delete();
			return $this;
		}
		
		public function update($data)
		{
			$data['text'] = trim($data['text']);
			$this->ORM_PDO->table($this->table_name)->where('id = ?', $data['id'])->update($data);
			return $this;
		}
		
		public function all($page=null, $count=null)
		{	
			if ($this->select)			
			{
				return $this->ORM_PDO->table($this->table_name)->where('post = ?', $this->select)->select('*');
			}
			else
			{			
				return $this->ORM_PDO->table($this->table_name)->select('*');
			}
		}	
		
		public function Count()
		{
			return $this->ORM_PDO->SQL('SELECT post, COUNT(post) FROM '.$this->table_name.' GROUP BY post;');			
		}
		
		public function clear($post)
		{
			$this->ORM_PDO->table($this->table_name)->where('post = ?', $post)->delete();
		}
		
		public function find($search)
		{
			
		}
		
		public function select($where, $param, $select='*')
		{					
			return $this->ORM_PDO->table($this->table_name)->where($where, $param)->select($select);
		}
				
		public function slice($unixtime)
		{
			return $this->ORM_PDO->SQL('SELECT post, COUNT(post) FROM '.$this->table_name." WHERE unixtime > $unixtime GROUP BY post;");
		}
		
		public function get($id)
		{
			return $this->ORM_PDO->table($this->table_name)->where('id = ?', $id)->select()[0];
		}		
	}
	
	
	
	
	
		//~ public function Select()
		//~ {
			
			
		//~ }
		
		//~ public function Insert()
		//~ {
			
			
		//~ }
		
		
		//~ public function Delete()
		//~ {
			
			
		//~ }
		
		//~ public function Reset()
		//~ {
			//~ $this->current['post'] = null;
			//~ $this->current['comment'] = null;
		//~ }
	

/*
==================================================================================================================================
			Подключим модуль к платформе
==================================================================================================================================
*/	
	
		
	
	//Загружаем конфигурацию
	$config = $this->config->get(__file__);
	
	
	//Если в конфиге нет обязательных параметров, то попробуем поставить их по умолчанию
	if ( !isset($config['table']['posts']) ) 		$config['table']['posts'] 		= 'posts';
	if ( !isset($config['table']['comments']) ) 	$config['table']['comments']	= 'comments';	
	if ( !isset($config['table']['visits']) ) 		$config['table']['visits']		= 'visits';	
	if ( !isset($config['db']['pdo']) )				$config['db']['pdo']			= 'sqlite:engine/database/blog.sqlite';
	
	
	
	//Создаем подключение к базе
	$PDO = new \PDO($config['db']['pdo'], $config['db']['user'], $config['db']['password']);
	//Построем ORM
	$ORM = new \ORM_PDO($PDO);	
	//Создадим экземпляр кода
	$blog = new quick_blog($ORM);
	
	$blog->config = $config;
	
	
	return $blog;
	
