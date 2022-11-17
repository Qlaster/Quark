<?php
	namespace QyberTech\Presenter;

	class Presenter
	{
		//Конфигурация (заполняется по умолчанию при контрукторе)
		public $config;

		//Ccылка на файл шаблона и рабочую директорию
		private $file_link;
		private $file_path;
		//переменная хранит массив времен изменений всех tpl файлов, составляющих страницу
		private $file_time =[];
		//Переменная, хранящая ссылки на внешние фрагменты файла
		//~ private $file_chunks;

		//Хранит оносительный путь url до ресурсов.
		public $base_link = null;
		//Если указать, то добавит <base> тег в шапку страницы
		public $base_html = null;
		//Добавляет этот код в заголовок (в шапку) html
		public $head = null;

		//Счетчик открытых/закрытых литеральных тегов. $literal_count['название тега'] = количество открытий (вложений)
		private $literal_count = array();


		public function __construct($config=[])
		{
			$this->config['left_delimiter']		= "{";		// the left delimiter for template tags
			$this->config['right_delimiter']	= "}";		// the right delimiter for template tags
			$this->config['error_reporting']	= E_ALL & ~E_NOTICE;	//Настройки вывода ошибок. Параметры идентичны php функции error_reporting();
			$this->config['url_link_tag']		= '~/';		//Тег, обозначающий	url путь до шаблона. При компиляции, этот тег будет заменен на путь до файла
			$this->config['skip_comments']		= false;		//Исключать комментарии в итоговом файле

			//Создаем правила интерпретации управляющих структур
			$this->config['snippets']['foreach']['open']	= '<?php foreach (';
			$this->config['snippets']['foreach']['close']	= ') { ?>';
			$this->config['snippets']['while']	['open']	= '<?php while (';
			$this->config['snippets']['while']	['close']	= ') { ?>';
			$this->config['snippets']['for']	['open']	= '<?php for (';
			$this->config['snippets']['for']	['close']	= ') { ?>';
			$this->config['snippets']['if']		['open']	= '<?php if (';
			$this->config['snippets']['if']		['close']	= ') { ?>';
			$this->config['snippets']['elseif']	['open']	= '<?php } elseif (';
			$this->config['snippets']['elseif']	['close']	= ') { ?>';
			$this->config['snippets']['=']		['open']	= '<?php echo (';
			$this->config['snippets']['=']		['close']	= ');?>';


			//Переменные
			$this->config['variable']['$$']		['open']	= '<?php echo ($';
			$this->config['variable']['$$']		['close']	= ');?>';
			$this->config['variable']['$']		['open']	= '<?php echo (htmlspecialchars($';
			$this->config['variable']['$']		['close']	= '));?>';


			//Теги, разбор в которых не производится
			$this->config['literal']['literal']		['open']	= '<literal>';
			$this->config['literal']['literal']		['close']	= '</literal>';
			$this->config['literal']['comment']		['open']	= '<!--';
			$this->config['literal']['comment']		['close']	= '-->';
			$this->config['literal']['script']		['open']	= '<script';
			$this->config['literal']['script']		['close']	= '</script>';
			$this->config['literal']['style']		['open']	= '<style';
			$this->config['literal']['style']		['close']	= '</style>';
			$this->config['literal']['php']			['open']	= '<?php';
			$this->config['literal']['php']			['close']	= '?>';

			//Статические теги
			$this->config['static']['end']			= '<?php } ?>';
			$this->config['static']['else'] 		= '<?php } else { ?>';
			$this->config['static']['php'] 			= '<?php ';
			$this->config['static']['/php']			= ' ?>';
			$this->config['static']['base']			= '	<base href="<?=$this->base_html?>">'."\r\n";	//Если указать, то добавит <base> тег, взяв значение из поля base_html


			//Параметры компиляции
			$this->config['compilation']['caсhe']	= true;		//Не перекомпилировать шаблон, если версия файла идентична и он не измнился.
			$this->config['compilation']['folder']	= sys_get_temp_dir().'/presenter';
			$this->config['compilation']['extent']	= 'php';
			$this->config['compilation']['nobody'] 	= true;		//Автотрансляция тегов в php код неизвестных тегов. Предполагается, что если мы не смогли определить что это - то это php

			//Параметры управления шаблонами
			$this->config['templates']['folder']	= "";	//Явное указание директории с шаблонами

			//Жёсткая замена тегов
			#$this->config['replace']['demotest']	= 'replace_demo_test';

			//Перезапишем стандартные параметры переданными значениями
			$this->config = array_replace_recursive($this->config, $config);

			//Заменим переменную окружения на реальный путь
			$this->config['compilation']['folder'] = str_replace('%TEMP%', sys_get_temp_dir().'/', $this->config['compilation']['folder']);
		}

		//Указывает, над каким файлом выполнить операции (html файл шаблона)
		public function file($file_link)
		{
			$this->file_time = [];

			//Если явно указали директорию хранения, то будем искать в ней. Но проработаем момент, что бы если передали полный путь, не вставлять его еще раз
			if ( ($this->config['templates']['folder']) and (strpos($file_link, $this->config['templates']['folder'].DIRECTORY_SEPARATOR) !== 0) )
			{
				$file_link = $this->config['templates']['folder'].DIRECTORY_SEPARATOR.$file_link;
			}

			if ( !file_exists($file_link) )
			{
				//...выбрасываем предупреждение
				trigger_error ( "Requested file '$file_link' not found." , E_USER_WARNING );
			}
			else
			{
				$this->file_link = $file_link;
				$this->file_path = pathinfo($this->file_link)['dirname'].'/';
				$this->file_path = str_replace('//', '/', $this->file_path);
			}

			return $this;
		}

		//Указывает, как добраться до ресурсов html файла по url.
		public function themelink($link)
		{
			$this->base_link = $link;
			return $this;
		}


		//Метод считает открытие/закрытие литеральных тегов. Ты отправляешь ему тег, он определеяет, литеральный он или нет. Если литеральный, то сморит, открытый или закртытый.
		//Если открытый - увеличивает счетчик. Если закрытый - уменьшает. Если тег пуст, то возвращает true/false в зависимости от того, открыт/закрыт хоть один литеральный тег.
		//Данный метод очень удобен для фильтрации тегов.
		private function literal_count($literal_tag='')
		{
			//Если не указали тег, то проверяем на открытость литеральных тегов
			if ($literal_tag == '')
			{
				//Просто найдем любой тег, который открыт. Ну хоть один...
				foreach ($this->literal_count as $tag_name => $flag_open)
				{
					if ($flag_open != 0) return true;
				}
				return false;
			}



			//Проходим по всем тегам
			foreach ($this->config['literal'] as $tag_name => $syntax)
			{

				//Если тег одновременно открыли и закрыли - то не нужно менять состояние (например такой тег <!-- C -->)
				if  (
						( mb_substr($literal_tag, 0, mb_strlen($syntax['open']) ) == $syntax['open'] )
							and
						( mb_substr($literal_tag,  -mb_strlen($syntax['close']) ) == $syntax['close'] )
					)
				{
					//Не смотря на то, что тег не меняет счетик открытых/закрытых тегов, это все таки литеральный (цитируемый тег)
					//Значит нам нужно вернуть флаг, что его обрабатывать не нужно
					return true;
				}

				if (strpos($literal_tag, $syntax['open']) === 0)
				{

					if (! isset($this->literal_count[$tag_name]) ) $this->literal_count[$tag_name] = 0;

					$this->literal_count[$tag_name]++;

					return true;
					break;
				}

				if (strpos($literal_tag, $syntax['close']) === 0)
				{
					if (! isset($this->literal_count[$tag_name]) ) $this->literal_count[$tag_name] = 0;

					$this->literal_count[$tag_name]--;
					if ($this->literal_count[$tag_name]<0)	$this->literal_count[$tag_name] = 0;

					return true;
					break;
				}
			}
		}


		private function compile_resource($tpl_string)
		{
			$htmlhead = strstr($tpl_string, "<head>");
			$htmlhead = strstr($htmlhead, "</head>", true);

			//дополним шапку base тегом, если нам его указали
			if ($this->base_html)	$head .= "	\r\n".$this->config['static']['base'];
			//Доп. код в заголовок
			if ($this->head)		$head .= "	\r\n".$this->head;

			if ($head)
				$tpl_string = str_replace($htmlhead, $htmlhead .= $head, $tpl_string);

			preg_match_all("<script.*?src=[\"'](.*?)[\"'].*?>", $tpl_string, $scripts_tags);
			preg_match_all("<link.*?href=[\"'](.*?)[\"'].*?>",  $htmlhead, $links_tags);

			$resource_tags[0] = array_merge($scripts_tags[0], $links_tags[0]);
			$resource_tags[1] = array_merge($scripts_tags[1], $links_tags[1]);

			//Разберем начало строки с ресурсами, что бы избежать ошибок
			foreach ($resource_tags[1] as $_iterator => &$_stript_tag)
			{
				//Для этого тега уже был поставлен симлинк, пропустим его
				if (mb_strpos($_stript_tag, $this->config['url_link_tag']) === 0) continue;
				//Если указан полный путь на внешний ресурс - нам торже не требуется учавствовать в этом
				if (mb_strpos($_stript_tag, 'http://') === 0) continue;
				if (mb_strpos($_stript_tag, 'https://') === 0) continue;

				//Создадим правила замены для ресрсов
				$resource_replace['search'][$_iterator] = $resource_tags[0][$_iterator];
				$resource_replace['replace'][$_iterator] = str_replace($_stript_tag,  $this->config['url_link_tag'] . $_stript_tag, $resource_replace['search'][$_iterator]);
				// $_stript_tag = $this->config['url_link_tag'] . $_stript_tag;
			}

			//Изменим шаблон
			$tpl_string = str_replace($resource_replace['search'], $resource_replace['replace'], $tpl_string);

			//Заменим все относительные симлинки путями к шаблону
			return str_replace($this->config['url_link_tag'], '<?=$this->base_link?>', $tpl_string);
		}


		//Метод компилирует строку (теги LITERAL учитываются)
		public function compile_data($tpl_string)
		{
			$result = '';
			$this->literal_count = array();

			//Сортируем конфиг в обратной последовательности. что бы решить проблемы поиска
			krsort($this->config['snippets']);
			krsort($this->config['variable']);

			//Выполняем "магический" replace
			foreach ( (array) $this->config['replace'] as $key => $value)
			{
				$tpl_string = str_replace($key, $value, $tpl_string);
			}

			//Заменим симлинки относительными путями
			$tpl_string = $this->compile_resource($tpl_string);

			//Получем список всех тегов
			$all_tags = $this->get_tags($tpl_string);

			//Сбрасываем счетчики литеральных тегов. Если в документе они были не парными, это может привести к багам
			$this->literal_count = array();

			//Двигаемся от тега к тегу, по телу документа
			foreach ($all_tags as $key => &$tag)
			{
				//Узнаем позицию текущего тега
				$pos = mb_strpos($tpl_string, $tag);
				//Переносим часть документа до тега в результат (не обрабатываемые даннные)
				$result .= mb_substr($tpl_string, 0, $pos);
				//Удаляем данные до тега и сам тег
				$tpl_string = mb_substr($tpl_string, $pos+mb_strlen($tag));

				//Если нас попросили не выводить комментарии - так же их пропустим
				if ((mb_substr($tag, 0, 4) == '<!--') and ($this->config['skip_comments'])) continue;

				//Обработка литерального тега
				$literal_tag = $this->literal_count($tag);

				//Это тег шаблонизатора???
				if ( $this->is_tag($tag) )
				{
					//Если тег меняет литеральное состояние - это литеральный тег шаблонизатора. Его выводить не нужно.
					if ($literal_tag) continue;

					//Если нет открытых литеральных тегов - компилируем
					if (! $this->literal_count() )
					{
						$result .= $this->compile_tag($tag);
						continue;
					}
				}

				//Если вышестоящие условия отработали безрезультатно - игнорируем этот тег и выводим (он не попадает под правила.)
				$result .= $tag;
			}


			//Добавляем хвост шаблона, который остался без тегов
			$result .= $tpl_string;


			//Удаляем избыточные теги. Если в опциях установлен флажек.
			if ($this->config['php_mutex'])
			{
				$result = preg_replace('#\?\>\s*?\<\?php#', " ", $result);
			}
			/*$result = str_replace('?><?php', ' ', $result);*/


			//Возвращаем откомпилированный шаблон
			return $result;

		}


		//Соберем TPL файл со всеми зависимостями
		private function tpl_get_contents($tpl_file)
		{
			//Запишем последнее время изменения файла TPL
			$this->file_time[$tpl_file] = filectime($tpl_file);
			$document = file_get_contents($tpl_file);

			$L = $this->config['left_delimiter'];
			$R = $this->config['right_delimiter'];

			//~ $math = "#(\\$L"."require\(['\"](.*?)['\"]\)\\$R)#";
			//~ $math = "#(\\$L"."require[ ]*[(]?['\"](.*?)['\"][)]?\\$R)#";
			$math = "#(\\$L(section|require)[ ]*[(]?['\"](.*?)['\"][)]?\\$R)#";

			do
			{
				$tpl_content = null;

				//Поищем в файле ссылки на внешние TPL
				if (preg_match_all($math, $document, $list))
				{
					//Ссылка, куда вставить файл
					$require_pattern = $list[0];
					//Путь к файлу
					$require_file    = $list[3];

					//Заменяем
					foreach ($require_pattern as $index => $pattern)
					{
						$tpl_content = null;

						if (is_file($this->file_path.$require_file[$index]))
						{
							//~ $tpl_content = file_get_contents($this->file_path.$require_file[$index]);
							$tpl_content = $this->tpl_get_contents($this->file_path.$require_file[$index]);
							//~ //$document = str_replace($pattern, $tpl_content, $document);
						}
						else
						{
							trigger_error("Не могу найти фрагмент ".$this->file_path.$require_file[$index], E_USER_WARNING);
						}

						$document = str_replace($pattern, $tpl_content, $document);
					}
				}
			} while ($tpl_content); //Выполняем до тех пор, пока удается подтянуть новый контент из ссылок

			return $document;
		}


		//Ты ей () тег, а она тебе скомпилированный php код этого тега
		private function compile_tag($tag)
		{

			//Очистим делимитеры тега.
			$tag = mb_substr($tag, mb_strlen($this->config['left_delimiter']));
			$tag = mb_substr($tag, 0, mb_strlen($tag)-mb_strlen($this->config['right_delimiter']));

			$tag = trim($tag);

			//Первым делом проверяем на статические теги
			if ( array_key_exists($tag, $this->config['static']) )
			{
				//Если нашли
				return $this->config['static'][$tag];
			}


			//===== ПРАВИЛА СБОРКИ ТЕГОВ =========
			//Пытаемся определить, что же нам подсунули.
			//Этап 1. Это переменная?
			//Ставим её первой, потому что это самое частое выражение в шаблонах. Рискуем сэкономить на спичках.
			//Из-за ссылок на конфиг - это не код, а какой то адский пиздец.... Еще немного и можно переписать на брайнфаке. =(
			//Пока, я сделал все что мог, что бы это хоть как то читалось.	//Будет время - введу define
			foreach ($this->config['variable'] as $key => $value)
			{

				//Тег найден. Собираем.
				if ( strpos($tag, $key) === 0)
				{
					//Вырезаем указатель переменной вместе с asis
					$tag = mb_substr( $tag, mb_strlen($key));
						//Компилируем переменную по правилам таблицы компиляции для переменной с необходимостью экранирования
					return $value['open']
							. $tag .
						   $value['close'];
				}
			}



			//Этап 2. Это управляющее выражение?
			$expression = mb_substr( $tag, 0, mb_strpos($tag, ' ') );
			if ($expression == '') $expression = mb_substr( $tag, 0, mb_strpos($tag, '(') ); //ЭТО Я ПИСАЛ???? ЧТО Я ИМЕЛ ВВИДУ??? ЗАЧЕМ????

			if ($expression != '')
			{
				//Ищем управляющее выражение в таблице компиляции
				if (array_key_exists($expression, $this->config['snippets']))
				{
					//отрезаем управляющую структуру в теге (что бы потом заменить её на необходимую для php)
					$tag = mb_substr( $tag, mb_strlen($expression) +1 ); //-1 - не забыть

					return $this->config['snippets'][$expression]['open']
						   . $tag .
						   $this->config['snippets'][$expression]['close'];
				}
			}

			//Этап 3. Разобрать не удалось. Транслируем сырой вывод в php. Конечно, если это разрешено.
			if ($this->config['compilation']['nobody'])
			{
				return 	$this->config['static']['php']
						. $tag .
						$this->config['static']['/php'];
			}
		}


		//Функции (а точнее, метод класса) передается список переменных, которые должны быть выведены на шаблон. Метод компилирует шаблон и выводит его браузеру. Источник шаблона берется в методе $tpl_file.
		public function display($vars_array = array())
		{

			$result = '';
			//Запоминаем код вывода ошибок
			$error_reporting = error_reporting();

			//Устанавливаем код вывода ошибок из конфига
			error_reporting($this->config['error_reporting']);


			if (isset($vars_array))
			{
				//Собираем все переменные из массива.
				foreach ($vars_array as $var_name => $var_value)
				{
					$$var_name = $var_value;
				}
			}

			//Если базовый путь не указан, возмем путь из файла
			if ($this->base_link === null)
			{
				$url_path = pathinfo($_SERVER['SCRIPT_NAME'])['dirname'];
				$url_path = $url_path.DIRECTORY_SEPARATOR.$this->file_path;
				$this->base_link = str_replace('//', '/', $url_path);
			}

			//Компилируем файл шаблона
			$compil_file = $this->compile();

			//Если удалось скомпилировать файл, то отправляем на вывод
			if (($compil_file) and (file_exists($compil_file)))
			{
				require($compil_file);
				$result = true;
			}

			//Устанавливаем код вывода ошибок, который стоял по умолчанию
			error_reporting($error_reporting);

			return $result;
		}



		//Компилируем шаблон и выводим его как строку. Источник шаблона берется в свойстве $tpl_file.
		public function compile()
		{
			//Если файл шаблона не найден
			if (! file_exists($this->file_link)) return false;

			//Если каталог пуст - устанавливаем текущий. А что еще остается делать?
			if ($this->config['compilation']['folder'] == '') $this->config['compilation']['folder'] = getcwd();

			//Смотрим в кеш. Может быть мы уже компилировали его? Если да, то возвращаем. (Само собой, если это разрешено настройками)
			if ($this->config['compilation']['caсhe'] == 'true')
			{
				$cache_file = $this->compile_gen_filename($this->file_link);
			}

			if (isset($cache_file) and file_exists($cache_file))
			{
				//Если кеш найден - возвращяем ссылку на него
				return $cache_file;
			}

			//Загружаем шаблон документа tpl
			$document = $this->tpl_get_contents($this->file_link);

			//Компилируем содержимое
			$compil = $this->compile_data($document);

			//Генерируем имя файла для кеша
			$cache_file = $this->compile_gen_filename($this->file_link);

			//Проверим существование директории, в которую мы намереваемся записать
			if (!file_exists($this->config['compilation']['folder']) and (!mkdir($this->config['compilation']['folder'], 0777, true)))
			{
				trigger_error("Не могу создать временную директорию", E_USER_WARNING);
				return false;
			}

			$this->Clear_Cache_File($this->file_link);

			// Пишем содержимое в файл,
			// и флаг LOCK_EX для предотвращения записи данного файла кем-нибудь другим в данное время
			file_put_contents($cache_file, $compil, LOCK_EX);

			if (file_exists($cache_file))
			{
				return $cache_file; //если все хорошо - возвращаем ссылку на скомпилированный шаблон
			}
			else
			{
				return false;
			}

		}


		//Получить список переменных шаблона
		public function vars()
		{
			//Получаем все теги шаблонизатора
			$tags = $this->tag_list();

			$result = array();

			//Проходимся по списку полученных тегов
			foreach ($tags as $value)
			{
				//Регулярка, под которую подпадают переменные
				$math = '#\$+[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff\[\'\"\]]*#';
				if ( preg_match_all($math, $value, $res) )
				{
					//Прежний код, где ключей не было
					$result = array_merge((array)$result, $res[0]);
				}
			}

			return array_unique($result);
		}



		//Список тегов, которые будут обработаны шаблонизатором
		public function tag_list()
		{
			$this->literal_count = array();

			//Если файл шаблона не найден
			if (! file_exists($this->file_link)) return false;

			//Загружаем содержимое файла
			$tpl_string = $this->tpl_get_contents($this->file_link);
			$tpl_string = str_replace(array("\r\n", "\r", "\n"), '', $tpl_string);

			//Получем список всех тегов
			return $this->get_tags($tpl_string);

			//TODO: Прежняя версия кода. Устарело
			//Получем список всех тегов
			$all_tags = $this->get_tags($tpl_string);

			//Двигаемся от тега к тегу, по телу документа
			foreach ($all_tags as $key => &$tag)
			{
				//Обработка литерального тега
				$literal_tag = $this->literal_count($tag);

				//Это тег шаблонизатора???
				if ( $this->is_tag($tag) )
				{
					//Если тег меняет литеральное состояние - это литеральный тег шаблонизатора. Его выводить не нужно.
					if ($literal_tag) continue;

					//Если нет открытых литеральных тегов - добавляем в выдачу
					if (! $this->literal_count() )
					{
						$result[] = $tag;
					}
				}
			}

			return (array) $result;
		}


		//Метод ищет в строке теги, и возвращает их массивом.
		public function get_tags($string)
		{
			if ($string == '') return array();

			$L = $this->config['left_delimiter'];
			$R = $this->config['right_delimiter'];

			//~ $math = "#(\\$L(.*?)\\$R)|(\<(.*?)\>)#";	//|(\<!--(.*?)--\>)
			//~ $math = "#(\\$L(.*?)\\$R)|(<!--(.*?)-->)|(\<(.*?)\>)#";	//|(\<!--(.*?)--\>)
			$math = "/\s*(\\$L(.*?)\\$R)|(<!--(.*?)-->)|(\<\?php(.*?)\?\>)|(\<(.*?)\>)\s*/s";

			if (preg_match_all($math, $string, $list))
			{
				//Лист содержит весь список тегов
				$list = (array) $list[0];
			}

			$result = [];
			//Проверяем, есть ли внури тегов html, теги шаблонизатора
			$math = "#(\\$L(.*?)\\$R)#";
			foreach ($list as $value)
			{
				//Если это литеральный тег - мы его пропустим. В нем не нужно копаться
				if ($this->literal_count($value))
				{
					$result[] = $value;
					continue;
				}

				if (preg_match_all($math, $value, $buflist))
				{
					$result = array_merge($result, $buflist[0]);
				}
				else
				{
					$result[] = $value;
				}
			}

			return (array) $result;
		}


		//Метод возвращает true, если указанная строка содержит тег оформленный в соответствии с правилами синтаксического оформления тегов шаблонизатора.
		//Проводится лишь поверхностный синтаксический анализ.
		private function is_tag($tag)
		{
			$tag = trim($tag);

			if  (
					( mb_substr($tag, 0, mb_strlen($this->config['left_delimiter']) ) == $this->config['left_delimiter']  )
						and
					( mb_substr($tag,  -mb_strlen($this->config['right_delimiter']) ) == $this->config['right_delimiter'] )
			    )
			{
				return true;
			}
			else
			{
				return false;
			}
		}



		//Метод генерирует имя скомпилированно tpl шаблона.
		//$filename - имя файла шаблона. $all_path - если true - то добавлять к сгенерированному имени файла путь до каталога.
		private function compile_gen_filename($filename, $all_path=true)
		{
			if (file_exists($filename))
			{
				//Если нам не известно из каких фрагментов состоит шаблон - заглянем в него и подгрузим эти сведения
				//(TODO: Это не самая производительная механика, вероятно, это можно сделать быстрее)
				if (!$this->file_time) $this->tpl_get_contents($filename);

				//Сумма дат изменений всех файлов, входящих в состав страницы (что бы перекомпилировать шаблон, если один из фрагментов изменится)
				$edit_date = date('YdmHis', (int) array_sum($this->file_time));

				//~ $filename = basename($filename, '.' . pathinfo($filename, PATHINFO_EXTENSION));
				$filename = str_replace(DIRECTORY_SEPARATOR, '-', $filename);
				$filename = $filename . "~$edit_date." . $this->config['compilation']['extent'];

				//Если попросили указать полный путь до файла
				if ($all_path)
					$filename = $this->config['compilation']['folder'].'/'. $filename;

				return $filename;
			}

		}


		//Очищает все версии кеша шаблона $tlp_filename. Опять же - потенциально опасна.
		private function clear_cache_file($tlp_filename)
		{
			//Выделяем имя tlp шаблона
			$tlp_filename = basename($tlp_filename, '.'.pathinfo($tlp_filename, PATHINFO_EXTENSION));

			//Создаем маску выборки
			$mask = $this->config['compilation']['folder'].'/'.$tlp_filename.'~*'.$this->config['compilation']['extent'];

			//Создаем список файлов, попадающих под эту маску
			$cache_list = glob($mask);

			//проходим по циклу и очищем
			foreach ($cache_list as $filename)
			{
				if (file_exists($filename))
				{
					unlink($filename);
				}
			}
		}
	}

