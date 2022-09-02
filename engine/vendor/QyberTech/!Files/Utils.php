<?php

	namespace QyberTech\Files;
	
	
	/*
		Расширение, отвечающее за мониторинг ресурсов, занимаемых CMS.
	*/
	
	class Q_SysUtils_MonRes
	{
	
		function __construct()
		{
			//Переменная необходима для реализации функции CMS_RunTime(). В нее записывается момент начала исполнения скрипта. 
			//Конечно, до этого момента происходят некоторые действия, производимые ядром и точкой входа, но они настолько малы по затратам ресурсов,
			//что этим показателем можно пренебречь.
			$this->RunTime(true);
		
		}
		
		//Возвращает время - точку отсчета замера
		function StartTime()
		{
			return microtime(1);
		
			//Прежний код 
			/*
			// фиксация времени начала генерации страницы  
			$begin = microtime();  
			// матрица начального времени с секундами и миллисекундами  
			$arrbegin = explode(" ",$begin);  
			// Полное начальное время 
			$allbegin = $arrbegin[1] + $arrbegin[0]; 
			return $allbegin;
			*/
		}
	
		//Функция принимает точку отсчета замера и возвращает время, затраченное с момента этой точки.
		function StopTime($starttime)
		{
			return (microtime(1) -$starttime);
		
			//Прежний код
			/*
			// фиксация времени останова   
			$stop = microtime();  
			// матрица времени останова с секундами и миллисекундами  
			$arrend = explode(" ",$stop);  
			// Полное время останова 
			$allend = $arrend[1] + $arrend[0];  
			// вычитание из конечного времени начального  
			$alltime = $allend - $starttime;  
			return $alltime;
			*/
		}
		
		
		
		//Функция возвращает время исполнения скрипта.
		function RunTime($reset='')
		{
			static $CMS_GLOBAL_RUN_TIME_OF_SCRIPT;
		
			if (!$reset) 
			{ 
				return $this->StopTime($CMS_GLOBAL_RUN_TIME_OF_SCRIPT); 
			}
			else {$CMS_GLOBAL_RUN_TIME_OF_SCRIPT = $this->StartTime();}
		}
		
		
		//Возвращает количество памяти выделенной PHP
		function MemoryUse()
		{
			//Если нам передали аргумент, то...
			if (func_num_args() > 0) $byte = func_get_arg (1);
			return memory_get_usage()/1024;
		}
		
		//Возвращает пиковое значение объема памяти, выделенной PHP
		function MemoryPeak()
		{
			return memory_get_peak_usage()/1024;
		}
		
		
		
		
	}
	
	
	
	
	/*
		Различные функции для реализации всевозможных универсальных задач.
		
	*/
	
	
	//УБРАТЬ ЭТОТ КЛАСС В БУДУЩЕМ!!!!
	class Q_SysUtils_Tools
	{
	
		//Перевод строки в верхний регистр
		function STR_UPPER($string)
		{
			return mb_strtoupper($string, "utf-8");
		}

		//Перевод строки в нижний регистр
		function STR_LOWER($string)
		{
			return	mb_strtolower($string, "utf-8");

		}
	
		//Переводит строку в шестнадцатеричное представление
		function STR_StrToHex($str)
		{
			$i=0;
			$dlina= strlen($str);
		
			while ($i<$dlina)
			{
				$res = $res.dechex(ord($str{$i}));
				$i++;
			}
	
			return $res;
		}
	
		//Обратное преобразование шестнадцатеричных данных в строку
		function STR_HexToStr($hex)
		{
			$dlina= strlen($hex);
	
			for ($i = 0; $i < $dlina; $i=$i+2) 
			{
				$chh = $hex[$i].$hex[$i+1];
				$res = $res . chr(hexdec($chh));	
			}
			return $res;
		}


		//Возвращает текущую дату и время
		function Utils_GetDateTime()
		{
			return date("d.m.Y H:i:s");
		}


		//Список папок в каталоге. (только папок, без учета файлов)
		function Utils_DirList($dir)
		{
			$handle_content = opendir ($dir); //Открываем папку
			if ($dir != "") $dir = $dir. "/"; //Если нам подсунули не пустую папку, то ставим слеш в конце

			while (true)
				{       
					$cat = readdir ($handle_content);
					if ($cat === false) break;
					if (is_dir($dir.$cat) and ($cat != '.') and ($cat != '..'))     
					{     
						$result[] = $cat; 
					}
				}
			closedir($handle_content);
			return $result;
		}

		//По безопасности смотреть здесь http://www.php.su/security/?filesystem
	
		//Безопасная проверка на наличие папки (не проканает ../../ ну и прочие). Проверка проводится дословно.
		function Utils_DirExists($dir,$name)
		{
			$buf = $this->CMS_Utils_DirList($dir);
			//print_r($buf);
			if (count($buf) == 0) return false; //Если массив пуст
			if (array_search($name, $buf) !== false) return true;
		}

		//Безопасная проверка на наличие структуры папок. $dir - строка, с которой следует начать путь, $path - перечисляемый массив с папками пути. 
		//Массив начинается с 0  и далее указываются папки, в том порядке, в котором они должны идти. 
		function CMS_Utils_PathExists($dir,$path)
		{
			foreach ($path as &$value) 
			{
				if (! $this->Utils_DirExists($dir,$value)) return false;
				$dir = $dir.'/'.$value;
			}

			return true;
		}

		//Безопасная проверка на наличие файла. $dir - каталог, в котором следует искать требуемый файл, 
		//$file - имя файла, которое может быть потенциально опасно
		//НЕ ТЕСТИРОВАЛАСЬ!!!!!
		function CMS_Utils_FileExists($dir,$filename)
		{
			return file_exists($dir.'/'.basename("$filename")); // усечение пути
			//return true;
		}
	
		//Если массив ассоциативный - вернет true. Функция не очень оптимальна по производительности, но ничего лучше пока предложить не могу.
		function CMS_Utils_Is_AssocArray($arr) 
		{
			foreach ($arr as $k => $v)
			{
			    if (! is_numeric($k)) 
				{
				return true;
				}
			}
			return false;
			//Код ниже - неработоспособен
		 	//return (is_array($arr) && count(array_filter(array_keys($arr),'is_string')) == count($arr));
		}

		//Отдать клиенту файл
		function CMS_Utils_GiveFile($file)
		{
			if (!file_exists($file)) return false;

			header ("Content-Type: application/octet-stream");
			header ("Accept-Ranges: bytes");
			header ("Content-Length: ".filesize($file)); 
			header ("Content-Disposition: attachment; filename=".$file);  
			readfile($file);
			return true;
		}
		
		
	}
		
	
	class Q_SysUtils
	{
		public $MonRes;
		public $Tools;	

		function __construct()
		{
			$this->Tools = new Q_SysUtils_Tools;
			$this->MonRes = new Q_SysUtils_MonRes;
		}
		
	}
	
	
	




