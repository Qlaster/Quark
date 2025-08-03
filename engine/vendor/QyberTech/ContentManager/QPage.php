<?php

	//~ //Добавсление/изменение страницы
	//~ //Для изменения адреса страницы укажите в $page['url']  новый адрес
	//~ $page->set($url, $page)
	//~
	//~
	//~ //Удаление страниц, удаление версий документов, переводов и т.д.
	//~ $page->del($url_mask, $lang_mask='', $version_mask='')
	//~
	//~
	//~ //Изменение страницы
	//~
	//~
	//~ //получить страницы
	//~ $page->get($url_mask, $lang='', $version_mask='')
	//~
	//~
	//~ //Поиск по страницам
	//~ $page->search($query, $lang='', $version_mask='')
	//~
	//~
	//~ //Список версий документа
	//~ $page->versions($url, $lang='', $version_mask='')
	//~
	//~
	//~
	//~
	//~ //Измменение текущей версии документа
	//~
	//~
	//~
	//~ //список url по маске
	//~ $page->url($mask)


	namespace QyberTech\ContentManager;
	use QyberTech\ContentManager\QMeta;

	class QPage
	{

		private $PDO_INTERFACE;		//Интерфейс к базе данных

		protected $Table_Page;			//Название таблицы со страницами
		protected $Table_Content;		//Название таблицы с контентами
		protected $Table_Service;		//Название таблицы со служебными страницами

		public $meta;					//Интерфес работы с метаданными

		/*
		 *
		 * name: Конструктор класса. На входе принимает имена таблиц под контент, в которых будет хранить информацию. Спасибо, кэп!
		 * @param PDO, подключенный к базе
		 * @return void
		 *
		 */
		function __construct($PDO_interface, $tables=['page'=>'page', 'content'=>'content', 'service'=>'service', 'meta'=>'meta'])
		{

			//Если переданный клас не является PDO, то показываем ошибку
			if ( ! ($PDO_interface instanceOf \PDO) )
			{
				//...выбрасываем предупреждение
				trigger_error ( "Переданный интерфейс не является объектом PDO." , E_USER_WARNING );
				return false;
			}

			//Устанавливаем интерфейс к базе данных
			$this->PDO_INTERFACE 	= &$PDO_interface;
			//Переводим в режим предупреждений
			$this->PDO_INTERFACE->setAttribute( \PDO::ATTR_ERRMODE, \PDO::ERRMODE_WARNING );
			//PDO будет возвращать только ассоциативные массивы
			$this->PDO_INTERFACE->setAttribute( \PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
			//NULL преобразовывать в пустые строки.
			$this->PDO_INTERFACE->setAttribute( \PDO::ATTR_ORACLE_NULLS, \PDO::NULL_TO_STRING);

			//Объявляем имена таблиц
			$this->Table_Page 		= $tables['page']    ?? 'page';
			$this->Table_Content 	= $tables['content'] ?? 'content';
			$this->Table_Service 	= $tables['service'] ?? 'service';
			$this->Table_Meta 		= $tables['meta']    ?? 'meta';

			//Построим таблицы, если их нет
			$this->DBConstruct();

			//Подключим расширение для метаданных
			$this->meta = new QMeta($this->PDO_INTERFACE, $this->Table_Meta);
		}



		/*
		 *
		 * name: Создание (подготовка) базы со всеми табличками
		 * @param
		 * @return
		 *
		 */
		public function DBConstruct()
		{
			$this->PDO_INTERFACE->BeginTransaction();

			//- - - - - - - - - - - - - - - - - - - - - - - - - - - - -
			//       Создаем таблицу с перечислением страниц
			//- - - - - - - - - - - - - - - - - - - - - - - - - - - - -
			$table = $this->Table_Page;
			$stmt = $this->PDO_INTERFACE->prepare(
				"CREATE TABLE IF NOT EXISTS '$table'
					(
						'url',
						'title',
						'html',
						'public',
						'index',
						'lang',
						'sitemap',
						'version'

					);
				")->execute();
			//Создаем индекс
			$this->PDO_INTERFACE->prepare("CREATE INDEX IF NOT EXISTS 'index_page' on '$table' ('url');")->execute();


			//- - - - - - - - - - - - - - - - - - - - - - - - - - - - -
			//Создаем таблицу с контентом
			//- - - - - - - - - - - - - - - - - - - - - - - - - - - - -
			$table = $this->Table_Content;
			$stmt = $this->PDO_INTERFACE->prepare(
				"CREATE TABLE IF NOT EXISTS '$table'
					(
						'url',
						'name',
						'type',
						'data',
						'lang',
						'search',
						'hidden',
						'version'
					);
				")->execute();
			//Создаем индекс
			$this->PDO_INTERFACE->prepare("CREATE INDEX IF NOT EXISTS 'index_content' on '$table' ('url','search');")->execute();

			//- - - - - - - - - - - - - - - - - - - - - - - - - - - - -
			//Создаем таблицу с сервисными страницами (с кодами ошибок)
			//- - - - - - - - - - - - - - - - - - - - - - - - - - - - -
			//~ $table = $this->Table_Service;
			//~ $stmt = $this->PDO_INTERFACE->prepare(
				//~ "CREATE TABLE IF NOT EXISTS '$table'
					//~ (
						//~ 'name',
						//~ 'html',
						//~ 'lang'
					//~ );
				//~ ")->execute();


			//Применяем изменения
			$this->PDO_INTERFACE->Commit();

		}


		/*
		 *
		 * name: Получить страницу
		 * @param В масках можно использовать символы *
		 * @return
		 *
		 */

		public function get($url, $lang=null, $version_mask=null)
		{
			$url = strtolower($url);
			$table_page 	= $this->Table_Page;
			$table_content 	= $this->Table_Content;

			//Составим запрос к базе, основываясь на переданных параметрах
			$where = ' ( LOWER(url) = :url ) ';

			if ($lang !== null)
			{
				$where .= ' and ( lang = :lang )';
			}



			//Первое, что нам предстоит сделать, это найти страницу в базе. Составляем запрос
			$STH = $this->PDO_INTERFACE->prepare("SELECT * FROM '$table_page' WHERE ($where);");
			$STH->bindParam(':url', $url);
			if ($lang !== null) $STH->bindParam(':lang', $lang);

			$STH->execute();

			//Узнаем есть ли страница с текущим url
			$page = $STH->fetchAll();

			if (count($page) == 0) return null;
			$page = $page[0];


			//Маска на версию документа
			if ($version_mask !== null)
			{
				$version_mask = str_replace('*', '%', $version_mask);
				$version_mask = str_replace('?', '_', $version_mask);

				$where .= ' and ( version LIKE :version )';
			}
			else
			{
				$where .= ' and ( version = :version )';
				$version_mask = $page['version'];
			}


			$STH = $this->PDO_INTERFACE->prepare("SELECT * FROM '$table_content' WHERE ($where);");
			$STH->bindParam(':url', 	$url);
			if ($lang 			!== null) $STH->bindParam(':lang', 		$lang);
			if ($version_mask	!== null) $STH->bindParam(':version', 	$version_mask);

			$STH->execute();
			$content = (array) $STH->fetchAll();


			foreach ($content as $value)
				$page['content'][$value['name']] = $value;


			return $page;
		}


		/*
		 *
		 * name: Установить (сохранить) страницу
		 * @param
		 * @return
		 *
		 */


		public function set($url, $page)
		{
			//Станица не может иметь тип. отличный от массива или объекта
			if ( ! (is_array($page) or is_object($page)) ) return false;

			//Явно приводим url к строке
			$url = strval($url);
			$page['lang'] = strval($page['lang']);
			$page['html'] = strval($page['html']);
			//Заголовок страницы подсмотрим из содержимого
			$page['title'] = $page['content']['title']['data'];

			//Если нам не указали новый адрес, логично считать, что следует оставить старый
			if (! isset($page['url']) ) $page['url'] = $url;



			//Получаем дату изменения, что бы записать версию
			$new_version = date('Y-m-d H:i:s');



			//~ $xxx = "url_94000";


			//$STH = $this->PDO_INTERFACE->query("SELECT url FROM page WHERE ( url = '$xxx' );");

			//~ $STH = $this->PDO_INTERFACE->prepare("SELECT url FROM page WHERE ( url = '$xxx' );");
			//~ $STH->execute();
			//~
			//~ $row = $STH->fetchAll();
			//~ print_r($row);

			//var_dump($STH->fetch());


			$table_page 	= $this->Table_Page;
			$table_content 	= $this->Table_Content;



			//Начинаем транзакцию
			$this->PDO_INTERFACE->BeginTransaction();


			//Первое, что нам предстоит сделать, это найти страницу в базе. Составляем запрос
			$STH = $this->PDO_INTERFACE->prepare("SELECT * FROM '$table_page' WHERE ( (url = :url) and (lang = :lang) );");


			//Если странице нужно присновить новый адрес
			if ($url != $page['url'])
			{
				//Узнаем, есть ли страница с таким адресом
				$STH->bindParam(':url', $page['url']);
				$STH->bindParam(':lang', $page['lang']);
				$STH->execute();

				//Узнаем есть ли страница с текущим url
				$new_url = $STH->fetchAll();

				//Если страница с новым именем существует...
				if (count($new_url) != 0 )
				{
					//...выбрасываем предупреждение
					trigger_error ( "Попытка изменения адреса для страницы '$url' провалилась. Страница с новым именем уже существует." , E_USER_WARNING );

					//Закрываем транзакцию
					$this->PDO_INTERFACE->Commit();
					return false;
				}
			}



			$STH->bindParam(':url', 	$url);
			$STH->bindParam(':lang', 	$page['lang']);
			$STH->execute();

			//Узнаем есть ли страница с текущим url
			$current_url = $STH->fetchAll();

			//Если страницы нет, то составим запрос на добавление
			if (count($current_url) == 0)
			{
				//Добавление страницы
				$STH = $this->PDO_INTERFACE->prepare("INSERT INTO '$table_page' (url, title, html, lang, public, \"index\", sitemap, version) values (:url, :title, :html, :lang, :public, :index, :sitemap, :version);");
			}
			else
			{
				//Если есть - то запрос на обновление
				$STH = $this->PDO_INTERFACE->prepare("UPDATE '$table_page' SET url=:url, title=:title, html=:html, lang=:lang, public=:public, \"index\"=:index, sitemap=:sitemap, version=:version WHERE (url=:wurl and lang=:lang);");
				$STH->bindParam(':wurl',	$url);
			}


			//Подготавливам выражение для записи данных о странице
			//$STH = $this->PDO_INTERFACE->prepare("INSERT INTO page ('url', 'html', 'lang', 'index', 'enable', 'version') values (:url, :html, :lang, :index, :enable, :version);");

			//Данные для индекса нужно перевести в нижний регистр, что бы облегчить поиск.
			//TODO:ЧТО??? Я пока закоменчу это
			//if ( isset($page['index']) ) $page['index'] = mb_strtolower($page['index'], 'UTF-8');


			//Заполняем выражение данными
			$STH->bindParam(':url', 	 $page['url']	);
			$STH->bindParam(':title', 	 $page['title']	);
			$STH->bindParam(':html',	 $page['html']	);
			$STH->bindParam(':lang',	 $page['lang']	);
			$STH->bindParam(':public',	 $page['public']);
			$STH->bindParam(':index',	 $page['index']	);
			$STH->bindParam(':sitemap',	 $page['sitemap']);
			$STH->bindParam(':version',	 $new_version	);

			//Выполняем изменения
			$STH->execute();


			//Переходим к обработке содержимого сраницы (если есть, конечно)
			if ((isset($page['content'])) and (is_array($page['content'])))
			{
				//Подготавливам выражение для записи тегов с данными
				$STH = $this->PDO_INTERFACE->prepare("INSERT INTO '$table_content' (url, name, type, data, lang, search, hidden, version) values (:url, :name, :type, :data, :lang, :search, :hidden, :version);");

				foreach ($page['content'] as $tag_name => &$tag)
				{
					//~ //Если язык тега не установлен явно - устанавливаем его таким же, как и на странице.
					//~ if (!isset($tag['lang'])) $tag['lang'] = $page['lang'];
					//~ //Если язык указан как пустой (по умолчанию), но у страницы он указан явно, то устанавливаем его таким, как на странице
					//~ if (($tag['lang'] == '') and ($page['lang'] != '')) $tag['lang'] = $page['lang'];

					//Язык тега должен совпадать с языком страницы
					$tag['lang'] = $page['lang'];

					if ( isset($tag['search']) ) $tag['search'] = mb_strtolower($tag['search'], 'UTF-8');

					//Заполняем выражение данными
					$STH->bindParam(':url', 	$page['url']);
					$STH->bindParam(':name',	$tag_name);
					$STH->bindParam(':type',	$tag['type']);
					$STH->bindParam(':data',	$tag['data']);
					$STH->bindParam(':lang',	$tag['lang']);
					$STH->bindParam(':search',	$tag['search']);
					$STH->bindParam(':hidden',	$tag['hidden']);
					$STH->bindParam(':version',	$new_version);

					//Выполняем изменения
					$STH->execute();
				}
			}

			//Завершаем транзакцию.
			return $this->PDO_INTERFACE->Commit();
		}


		//Показ всех страниц
		public function all($limit=null, $offset=null)
		{
			//Посмотри на использование limit
			//http://myrusakov.ru/sql-limit.html

			$table_page 	= $this->Table_Page;
			$table_content 	= $this->Table_Content;

			//Если хотят использовать оконную функцию
			if ($limit or $offset)
			{
				$limit = intval($limit);
				$offset = intval($offset);

				$limit = "LIMIT $offset, $limit ";
			}

			//Первое, что нам предстоит сделать, это найти страницу в базе. Составляем запрос
			$STH = $this->PDO_INTERFACE->prepare("SELECT * FROM '$table_page' $limit;");
			$STH->execute();

			return (array) $STH->fetchAll();
		}

		public function del($url, $lang_mask=null, $version_mask=null)
		{
			$table_page 	= $this->Table_Page;
			$table_content 	= $this->Table_Content;

			//Явно приводим url к строке
			$url = strval($url);

			//Собираем условие
			$where = '(url = :url)';
			if ($lang_mask)
			{
				$where .= 'and (lang like :lang_mask)';
				$lang_mask = strval($lang_mask);
				$lang_mask = str_replace('*', '%', $lang_mask);
				$lang_mask = str_replace('?', '_', $lang_mask);
			}

			if ($version_mask)
			{
				$where .= 'and (version like :version_mask)';
				$version_mask = strval($version_mask);
				$version_mask = str_replace('*', '%', $version_mask);
				$version_mask = str_replace('?', '_', $version_mask);
			}


			$STH = $this->PDO_INTERFACE->prepare("DELETE FROM $table_page WHERE $where; DELETE FROM $table_content WHERE $where;");

			//Заполняем выражение данными
			$STH->bindParam(':url', 	 $url);
			if ($lang_mask) 	$STH->bindParam(':lang_mask', 	 $lang_mask);
			if ($version_mask)	$STH->bindParam(':version_mask', 	 $version_mask);

			return $STH->execute();
		}

		public function count($unique=null)
		{
			$table_page = $this->Table_Page;
			$STH = $this->PDO_INTERFACE->prepare("SELECT COUNT(*) FROM '$table_page';");
			$STH->execute();

			$result = $STH->fetch();
			return array_shift($result);
		}


		public function versions($url, $lang=null)
		{
			//Явно приводим url к строке
			$url = strval($url);

			//Подготовим переменные, необходимые для формирования запроса
			$table_content 	= $this->Table_Content;
			$where = '(url = :url)';
			if ($lang !== null) $where .= ' and (lang = :lang)';

			//отправим запрос базе на разбор
			$STH = $this->PDO_INTERFACE->prepare("SELECT lang, version FROM '$table_content' WHERE ($where) GROUP BY version;");
			//Закинем параметры поиска
			$STH->bindParam(':url', 	$url);
			if ($lang !== null) $STH->bindParam(':lang', $lang);

			//Выполним запрос
			$STH->execute();

			//Вернем результаты выборки
			return $STH->fetchAll();

		}


		/*
		 *
		 * name: Очистка историй изменения и всех неиспользуемых записей
		 * @param
		 * @return
		 *
		 */
		public function clearing()
		{
			$table_page = $this->Table_Page;
			$table_content = $this->Table_Content;

			$STH = $this->PDO_INTERFACE->prepare("SELECT url, version FROM '$table_page';");
			$STH->execute();
			$pages = (array) $STH->fetchAll();

			if (!$pages) return true;
			foreach ($pages as $page)
			{
				$url = $page['url'];
				$version = $page['version'];
				$where[] = "not (url = '$url' and version == '$version')";
			}
			$where = implode(' and ', (array) $where);
			$STH = $this->PDO_INTERFACE->prepare("DELETE FROM '$table_content' WHERE $where");
			return $STH->execute();
		}


		/*
		 *
		 * name: Откат на предыдущую версию документа
		 * @param
		 * @return
		 *
		 */

		public function back($url, $lang, $version)
		{
			//Проверка даты версии на корректность. Если дата некоректна - выходим
			//TODO: На мой взгляд бессмысленная провека
			//if ( ! $this->validateDate($version) ) return false;

			//Явно приводим url к строке
			$url = strval($url);
			$lang = strval($lang);
			$version = strval($version);

			//Подготовим переменные, необходимые для формирования запроса
			$table_page 	= $this->Table_Page;

			//Подготавливаем запрос
			$STH = $this->PDO_INTERFACE->prepare("UPDATE '$table_page' SET 'version' = :version WHERE ((url = :url) and (lang = :lang));");

			//Закинем параметры поиска
			$STH->bindParam(':url', 	$url);
			$STH->bindParam(':lang', 	$lang);
			$STH->bindParam(':version', $version);

			//Выполним запрос
			$STH->execute();
		}




		public function search($query, $lang=null)
		{
			//переведем запрос в нижний регистр (у нас весь индекс храниться в нижнем регистре)
			$query = mb_strtolower($query, 'UTF-8');

			//Получаем ключевые поисковые слова
			$words = explode(' ', $query);



		}

		public function sitemap($export_file=null)
		{
			$table_page 	= $this->Table_Page;

			//Получаем URL, дату и язык для всех опубликованных страниц, индексация которых разрешена
			$STH = $this->PDO_INTERFACE->prepare("SELECT url, version, lang FROM '$table_page' WHERE ( (sitemap <> '') and (public <> '') );");

			//Выполняем
			$STH->execute();

			$url_list = (array) $STH->fetchAll();

			$text = '';

			foreach ($url_list as $record)
			{
				$loc	 = 'http://'.$_SERVER['HTTP_HOST'].'/'.htmlspecialchars($record['url']);
				$lastmod = htmlspecialchars(explode(' ', $record['version'])[0]);

				$text .= "	<url>
								<loc>$loc</loc>
								<lastmod>$lastmod</lastmod>
							</url>
						 ";
			}
			$text = "<?xml version='1.0' encoding='UTF-8'?>
						<urlset xmlns='http://www.sitemaps.org/schemas/sitemap/0.9'>
							$text
						</urlset>\n";

			//Если просили записать в файл - пишем. Если нет - возвращаем тело карты сайта
			return $export_file ? file_put_contents($export_file, $text) : $text;
		}



		//Экранирование строк sql
		private function escape_string($string)
		{
			return str_replace("'", "\'", $string);
		}


		//Метод очищает данные от лишних полей
		private function page_extract($page)
		{

			if ( isset($page['url'])	)	$fix_page['url'] 	= $page['url'];
			if ( isset($page['html'])	)	$fix_page['html']	= $page['html'];
			if ( isset($page['lang'])	)	$fix_page['lang']	= $page['lang'];
			if ( isset($page['index'])	)	$fix_page['index']	= $page['index'];
			if ( isset($page['public'])	)	$fix_page['public']	= $page['public'];
			if ( isset($page['sitemap']))	$fix_page['sitemap']= $page['sitemap'];
			if ( isset($page['version']))	$fix_page['version']= $page['version'];


			if ( is_array($page['content']) )
			{
				$fix_page['content'] = $page['content'];
			}

			return (array) $fix_page;
		}


		//Проверка на валидность даты
		private function validateDate($date, $format = 'Y-m-d H:i:s')
		{
			$d = \DateTime::createFromFormat($format, $date);
			return $d && $d->format($format) == $date;
		}


	}


