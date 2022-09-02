<?php

	use QyberTech\ContentManager\QPage;	

 	# ---------------------------------------------------------------- #
	#                  ОПИСАНИЕ     ИНТЕРФЕЙСА                         #
	# ---------------------------------------------------------------- #
	interface QPageInterface
	{
		function __construct($PDO_interface, $tablePage='page', $tableContent='content');
		
		// Получить страницу
		public function get($url, $lang=null, $version_mask=null);
		
		// Установить (сохранить) страницу
		public function set($url, $page);
		
		// Показ всех страниц
		public function all($limit=null, $offset=null);
		
		// Удаление страницы по url
		public function del($url, $lang_mask=null, $version_mask=null);
		
		// Количество доступных страниц
		public function count();
		
		// Узнать все версии документа по его url
		public function versions($url, $lang=null);
		
		// Откат на предыдущую версию документа
		public function back($url, $lang, $version);
		
		// Поиск по страницам требуемой информации
		public function search($query, $lang=null);
		
		//Построить карту сайта
		public function sitemap($export_file=null);
	}


	# ---------------------------------------------------------------- #
	#                 РЕАЛИЗАЦИЯ   ИНТЕРФЕЙСА                          #
	# ---------------------------------------------------------------- #
	class QPageAdapter extends QPage implements QPageInterface {}
	
	
	
	# ---------------------------------------------------------------- #
	# --------------[ СОЗДАЕМ И ПОДКЛЮЧАЕМ ИНТЕРФЕЙС ]---------------- #
	# ---------------------------------------------------------------- #
	
	//Загружаем конфигурацию
	$config = $this->config->get(__file__);
		
	
	//Если в конфиге нет обязательных параметров, то попробуем поставить их по умолчанию
	if ( !isset($config['table']['page']) ) 	$config['table']['page'] 	= 'page';
	if ( !isset($config['table']['content']) ) 	$config['table']['content']	= 'content';	
	if ( !isset($config['db']['pdo']) )			$config['db']['pdo']		= 'sqlite:engine/database/page.sqlite';
	
	
	//Создаем подключение к базе
	$dbh = new \PDO($config['db']['pdo'], $config['db']['user'], $config['db']['password']);
	
	//Подключаем Page к базе
	$page = new QPageAdapter($dbh, $config['table']['page'], $config['table']['content']);

	return $page;
