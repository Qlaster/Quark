<?php

	error_reporting(E_ALL & ~E_NOTICE);
	
	$content = $APP->controller->run('admin/autoinclude', $APP);
	
	
	//Загружаем меню
	//~ $menu = $APP->object->collection('admin')->get('mainmenu');
	//~ //Прикрепляем меню к странице
	//~ $content['nav']['main'] = $menu['ru'];
	//~ //Указываем пункт меню, который раскрыть
	//~ $content['nav']['main']['list']['content']['active'] = true;
	
	
		//~ $path = explode('/', $APP->url->page());
//~ 
	//~ foreach ($path as $key => &$value) 
	//~ {	
		//~ $tmp['list'][]['head'] = $value;
	//~ }
	//~ 
	//~ //Прикрепляем путь к контенту
	//~ $content['nav']['path'] = $tmp;
	
	
	//Подгружаем конфигурацию
	$config = $APP->config->get();
	
	//Подгружаем локаль конфига
	$content = array_merge($content, $config['ru']);
	
	//Получаем текущий адрес
	$content['form']['edit']['url']['info'] = $APP->url->home();

	//Получим директорию с шаблонами
	$templateDir = $APP->template->config['templates']['folder']; //.DIRECTORY_SEPARATOR
	
	//Прикрепляем список файлов html (для выбора шаблона)
	$content['form']['file']['list'][$templateDir] = PageTools::scandirs($templateDir, '*.htm*');
	
	//~ print_r($content['form']['file']['list']); die;
	
	//Удаляем административные темы
	unset($content['form']['file']['list'][$templateDir]['admin']);
	unset($content['form']['file']['list'][$templateDir]['admin-template']);
	
	
	//url не пустой, значит попросили создать или изменить
	if ($_GET['url'] !== null)
	{
		
		//Загружаем инфу о ней
		$page = $APP->page->get($_GET['url']);
				
		
		//Если такая страница существует
		if ($page !== null)
		{	
			
			//Заполняем переменные адреса
			$content['form']['edit']['url']['text'] = $page['url'];
			
			//Флажки
			$content['form']['edit']['public']['text'] = $page['public'];
			$content['form']['edit']['sitemap']['text'] = $page['sitemap'];
			$content['form']['edit']['index']['text'] = $page['index'];
							
			//Посмотрим, какой контент поддерживает страница
			if (file_exists($templateDir.$page['html']))	
			{				
				//Заполняем переменные html файла
				$content['form']['edit']['html']['text'] = $page['html'];

				//~ echo $page['html'];
				//Список переменных, поддерживаемых шаблоном
				$vars = $APP->template->file($page['html'])->vars();				
				//~ $vars = $APP->template->file("qmedia/q-single-service.html")->vars();

				//~ print_r($vars); die;
											
				//Получим список коллекций
				$buffer = $APP->object->collection_list();
				
				//Получим саписок всех провайдеров данных
				$content['providers'] = $APP->provider->listing();
				sort($content['providers']);
				

				//Построим имена всех объектов в коллекции 
				//TODO: Код альфа. Ибо при большом количестве объектор это будет безбожно лагать
				foreach ($buffer as $name)  
				{		
					$objects[$name] = $APP->object->collection($name)->names();		
				}

				//Конструируем объект для распарса страницы
				$PageTools = new PageTools($objects, $vars, $config['config']['var_filter'], $config['ru']);
				$PageTools->page = $page;
							
				//Собираем секции:				
				//Фреймы объектов
				$content['section']['frames'] 	= $PageTools->FrameConstruct();
				
				//Поле html
				$content['section']['html'] 	= $PageTools->HtmlConstruct();

				//Одиночные теги
				$content['section']['single'] 	= $PageTools->SingleTagConstruct();		
				
				//Строим
				//$content['section'] = PageTools::SectionConstruct($vars,  $APP->config->get()['ru']['tags']);
			}	
			else
			{
				//Если файл шаблона не найден
				echo 'File themes not found';
			}
		}
		else
		{
			
		}
	} 
	else
	{
		//
		//$content = array_merge($content, $APP->config->get()['ru']);
	}	




	//~ print_r($content); exit;
	



	//~ $themelink = $APP->url->home()."views/admin/";
	$APP->template->file('admin/content/page_add.html')->display($content);
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	//Список переменных, поддерживаемых шаблоном
	//$vars = $APP->template->file('akono/index.html')->vars();


	

	

	
	//print_r($content['section']['frames']); die;
	
	
	
	//print_r($content['section']['html'] ); die;
	
	
	
	//print_r($content['section']['single'] ); die;
	
	
	
	
	
	
	
		
	//Загружаем настройки тегов	
	//$frames		= PageTools::SectionConstruct($vars,  $config['ru']['tags'], $config['config']['var_filter']);
	
		

	//print_r($frames); die;
	
	//$content['section']['frames'] = PageTools::FrameConstruct($obj, $vars,  $config['ru']['tags'], $config['config']['var_filter']);
	//~ $select = 
	//~ 
	//~ //Каталог объектов
	//~ $content['section']['objects']['collection']['head'] = $config['ru']['objects']['collection'];
	//~ $content['section']['objects']['collection']['disabled'] = 'disabled';
	//~ 
	//~ 
	//~ $collection_buf = $APP->object->collection_list();
	

	
	
	
	
	//print_r($content['section']); die;
	
	
	//$content['section']['objects'] = array_merge($collection, $content['section']['objects']);
	
	//$content['section']['objects'] = array_merge($APP->object->collection_list(), $content['section']['objects']);

	
	//print_r($content['section']); die;

//~ 
	//~ print_r($content['form']['file']['list']);
//~ 
	//~ die;
	
	
	
	
	class PageTools
	{
		public $page = null;
			
		public function __construct($objects, $vars, $invisible_mask, $config)
		{
			$this->objects = $objects;
			$this->vars = $vars;
			$this->invisible_mask = $invisible_mask;
			$this->config = $config;
		}
		
		
	
		static function scandirs($start, $mask=null)
		{
			$files = array();
			try
			{
				$handle = opendir($start);				
			}
			catch (Exception $e)
			{
				trigger_error ( "Access denied! ($start)" , E_USER_WARNING );
				return $files;
			}
			//если не смогли получить доступ к папке
			if ($handle == null) return $files;
			
			while (false !== ($file = readdir($handle)))
			{
				if ($file != '.' && $file != '..')
				{
					if (is_dir($start.'/'.$file))
					{
						$dir = self::scandirs($start.'/'.$file, $mask);
						//Если в директории есть файлы - показыаем
						if (count($dir) > 0) $files[$file] = $dir;
					}
					else 
					{
						//Если попадает под маску - добавляем
						if ($mask !== null) 
						{
							if (fnmatch($mask, $file)) array_push($files, $file);
						}
						else
						{
							array_push($files, $file);
						}
					}
				}
			}
			krsort($files, SORT_STRING);
			closedir($handle);
			return $files; 
		}


		static function SectionConstruct($vars, $tags, $invisible_mask)
		{				
			foreach ($vars as $key => $var) 
			{
				//Получим имя переменной без лишних символов
				if ( preg_match('#[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*#', $var, $var_name) ) $var_name = $var_name[0];
				

				//Применяем маску срытости
				if (fnmatch($invisible_mask, $var_name)) continue;
				
				
				//TODO:НАПИШИ ЭТОТ КОД

				//Заглядываем в переданные правила...
				//Это следует интерпретировать как массив?
				if ( isset($tags['array'][$var_name]) )
				{
					$result[$var_name]['head'] = $tags['array'][$var_name];
					$result[$var_name]['type'] = 'array';
					$result[$var_name]['rule'] = true; //Помечаем, что это есть в правилах
				}
				
				//Это следует интерпретировать как строковой параметр?
				if ( isset($tags['string'][$var_name]) )
				{
					$result[$var_name]['head'] = $tags['string'][$var_name];
					$result[$var_name]['type'] = 'string';
					$result[$var_name]['rule'] = true;	//Помечаем, что это есть в правилах
				}
				
				//Это следует интерпретировать как параметр, хранящий html?
				if ( isset($tags['html'][$var_name]) )
				{
					$result[$var_name]['head'] = $tags['html'][$var_name];
					$result[$var_name]['type'] = 'html';
					$result[$var_name]['rule'] = true;	//Помечаем, что это есть в правилах
				}
				
				//В правилах о теге ничего не указано. Что ж... Попробуем опеределить сами.
			
				//Это использовано как массив?
				if (strpos($var, '['))
				{
					//Заполняем вывод
					if (!isset($result[$var_name]['head'])) $result[$var_name]['head'] = $var_name;
					//Помечяем типом массива, только если код выше не опознал его как нечто иное (html тоже может иметь вложеный список)
					if (!$result[$var_name]['type']) $result[$var_name]['type'] = 'array';
					
					//Определим, какие ключи есть в этом массиве
					//if ( preg_match('#\[.*\]*#', $var, $var_key) ) 
					if ( preg_match('#[\'\"].*?[\'\"]#', $var, $var_key) ) 
					{
						//Регулярка вернет название в ковычках. Удаляем
						$var_key = trim($var_key[0], "\"\'");
						
						$result[$var_name]['list'][$var_key] = $var_key;
						$result[$var_name]['list'] = array_unique($result[$var_name]['list']);					
						//$result[$var_name]['list'] = array_unique($result[$var_name]['list']);
					}
				}
				else
				{
					//Это строка?
					if (isset($result[$var_name]))  continue;
					
					$result[$var_name]['head'] = $var_name;
					$result[$var_name]['type'] = 'string';
					
					
					//А может быть это экранированная строка? (пример: $var или $$var)
					//Если это экранируемая переменная. тогда это html						
					if (mb_substr($var,1,1) == '$') $result[$var_name]['type'] = 'html';
				
				}
			}
			
			return (array)$result;
		}
		
		
		function FrameConstruct()
		{	
			
			$tags = $this->config['tags'];
			
			//Загружаем настройки тегов	
			$frames		= self::SectionConstruct($this->vars,  $tags, $this->invisible_mask);
						
	
			//Построим фреймы
			foreach ($frames as $f_name => &$f_frame) 
			{
			
				//Все одиночные теги объединяем в пустую коллекцию
				if (($f_frame['type'] == 'string')) 
				{
					//бред кобылы...	
					//~ $result['']['head'] = 'Строковые переменные:'; 
					//~ $result['']['type'] = 'string';
					//~ $result['']['list'][$f_name]['head'] = $f_frame['head'];
					
					unset($frames[$f_name]);
					continue;					
				}
				
				
				
				if ( ($f_frame['type'] != 'array')) 
				{ 
					unset($frames[$f_name]);
					continue;
				}
				
				//Проходимся по содержимому фрейма
				foreach ($f_frame['list'] as $f_tagname => &$f_tag) 
				{
					$f_tag = array('head'=>$f_tag);
					//Организуем уникальное название элемента на форме в формате frame_tagname
					$f_tag['name'] = '~'.base64_encode($f_name).':'.base64_encode($f_tagname);
					

					//=========================================
					//		ВЫПАДАЮЩИЙ СПИСОК
					//=========================================
					unset($collection_list);
					
					
					//Строим селектор выбора объекта
					//$collection_list['collection']['head'] = $this->config['objects']['collection'];
					//$collection_list['collection']['disabled'] = 'disabled';
					
					
					//$collection_buf = $APP->object->collection_list();
					
					//Построим выпадающий список из объектов					
					foreach ($this->objects as $name => $collection)
					{
						$item['head'] = $name;
						$item['name'] = $name;
						$item['disabled'] = 'disabled';
						
						$collection_list[] = $item;
						$html_tag_name = base64_encode($f_name).':'.base64_encode($f_tagname);
	
						foreach ($collection as $_item)
						{
							unset($item);
							$html_object_name = base64_encode($name).':'.base64_encode($_item);
							$item['head'] = $_item;
							$item['value'] = 'object:'.$html_object_name;
							
							if (($this->page['content'][$html_tag_name]['type'] == 'object') and
								($this->page['content'][$html_tag_name]['data'] == $html_object_name)
								)
									$item['active'] = true;
							$collection_list[] = $item;
						}

						

					}
					
					/*
					//... и построим из них структурированный список	
					foreach ($this->objects as $value) 
					{
						$buf['head'] = $value;
						$buf['value'] = 'object:'.base64_encode($value);
						
						$collection_list[] = $buf;
					}
					
					//С коллекцией объектов закончили. Перейдем к другим источникам
					$collection_list['other']['head'] 				= $this->config['objects']['other'];
					$collection_list['other']['disabled'] 			= 'disabled';
					
					$collection_list['noncollection']['head'] 		= $this->config['objects']['noncollection'];
					$collection_list['noncollection']['value']		= 'object:';
					$collection_list['text']['head'] 				= $this->config['objects']['text'];
					$collection_list['text']['value']				= 'text';
					$collection_list['source']['head'] 				= $this->config['objects']['source'];
					$collection_list['source']['value']				= 'source';

					//=========================================
					//		КОНЕЦ: ВЫПАДАЮЩИЙ СПИСОК
					//=========================================
					*/
					
					$f_tag['select'] = $collection_list;
					
					//$f_tag['edit']['head'] = ' or ';
					$f_tag['edit']['name'] = '='.$html_tag_name;
					if ($this->page['content'][$html_tag_name]['type'] == 'source')
							$f_tag['edit']['value'] = $this->page['content'][$html_tag_name]['data'];
					//$result[$f_name] = $f_frame;
				}
			}

			return $frames;
			
		}

		
		function HtmlConstruct_kek()
		{
			$tags = $this->config['tags'];
			
			//Загружаем настройки тегов	
			$html		= self::SectionConstruct($this->vars,  $tags, $this->invisible_mask);
			
			
			foreach ($html as $f_name => &$f_frame) 
			{				
				//Это html?
				if ($f_frame['type'] != 'html') 
				{
					unset($html[$f_name]);
				}
				else
				{
					
					foreach ($f_frame['list'] as $f_key => &$f_value) 
					{
						$f_value = null;
						$f_value['name'] = $f_key;
						$f_value['head'] = $f_key;
						$f_value['text'] = $this->page['content'][$f_name]['data'];
					}
					
					
					//От старгго кода
					//~ $f_frame['name'] = $f_name;
					//~ $f_frame['text'] = $this->page['content'][$f_name]['data'];
				}
			}
			
			//~ print_r($html); die;
			

			return (array) $html;
			
		}
		
				
		function HtmlConstruct()
		{
			$tags = $this->config['tags'];
			
			//Загружаем настройки тегов	
			$html		= self::SectionConstruct($this->vars,  $tags, $this->invisible_mask);
			
			
			foreach ($html as $f_name => &$f_frame) 
			{				
				//Это html?
				if ($f_frame['type'] != 'html') 
				{
					unset($html[$f_name]);
				}
				else
				{
					$f_frame['name'] = $f_name;
					$f_frame['text'] = $this->page['content'][$f_name]['data'];
				}
			}

		
			return (array) $html;
			
		}
		
		
		function SingleTagConstruct()
		{
			
			
			$tags = $this->config['tags'];
			
			//Загружаем настройки тегов	
			$single		= self::SectionConstruct($this->vars,  $tags, $this->invisible_mask);

			foreach ($single as $f_name => &$f_frame) 
			{
				
				//Все одиночные теги объединяем в пустую коллекцию
				if ($f_frame['type'] == 'string') 
				{
					$f_frame['name'] = $f_name;
					$f_frame['text'] = $this->page['content'][$f_name]['data'];
					
					//бред кобылы...	
					//$single['']['head'] = 'Строковые переменные:'; 
					//$single['']['type'] = 'string';
					//$single['']['list'][$f_name]['head'] = $f_frame['head'];
					
					//unset($single[$f_name]);
					continue;					
				}
				else
				{
					unset($single[$f_name]);
				}
	
			}
			
			$result['head'] = 'Cтроковые переменные';
			$result['list'] = $single;

			return (array) $result;
		}
		
	}
