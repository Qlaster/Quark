<?php
	error_reporting(E_ALL);

	$content = $APP->controller->run('admin/autoinclude', $APP);
	
	//Получаем список модулей
	$units_path = glob($APP->core_config['path_units']['app']."/*.php");
	
	//Получаем базовое имя модуля
	foreach ($units_path as $key => &$unitfile) 
	{
		$content['units']['list'][$key]['name']		= basename($unitfile);
		$content['units']['list'][$key]['create']	= date('d.m.Y H:i:s', filemtime($unitfile));
		$content['units']['list'][$key]['size']		= round(filesize($unitfile)/1024, 2) . ' Kb';
		
	//if (file_exists( unit_config_file($unitfile) ))
			$content['units']['list'][$key]['link']['config']	= "admin/app/codeeditor/?config=".unit_config_file($unitfile);
		$content['units']['list'][$key]['link']['code']		= "admin/app/codeeditor/?file=$unitfile";
		
		if ($_GET['sintax'])	
			$content['units']['list'][$key]['sintax']	= php_check_syntax($unitfile, $buffer);
	}
	
	//$content['units']['list'] = $units_path;
	
		
	//print_r($content['units']); die;
	
	

	//~ $content['code'] = file_get_contents('engine/lib/view/QTemplate.php');
	//~ $content['config'] = file_get_contents('engine/units/route.ini');
	//~ $themelink = $APP->url->home()."views/admin/";

	$APP->template->file('admin/components_units.html')->display($content);


	//Возвращает файл конфигураци
	function unit_config_file($file)
	{
		$path_parts = pathinfo($file);		
		return $path_parts['dirname'].'/'.$path_parts['filename'].'.ini';
	}


	function php_check_syntax($file, &$error) 
	{
	  // анализируем файл
	  exec("php -l ".$file, $error, $code);
	  // ошибок нет
	  if ($code == 0) 
	  {
		return true;
	  }
	  // ошибки есть
	  return false;
	}


	/**
	 * Syntax check PHP file
	 *
	 * @param string file path
	 *
	 * @return boolean checking result
	 */
	function syntax_check_php_file ($file) 
	{   
		// получим содержимое проверяемого файла
		@$code = file_get_contents($file);
		 
		// файл не найден
		if ($code === false) 
		{
			throw new Exception('File '.$file.' does not exist');
		}
		 
		// первый этап проверки
		$braces = 0;
		$inString = 0;
		foreach ( token_get_all($code) as $token ) 
		{
			if ( is_array($token) ) 
			{
				switch ($token[0]) 
				{
					case T_CURLY_OPEN:
					case T_DOLLAR_OPEN_CURLY_BRACES:
					case T_START_HEREDOC: ++$inString; break;
					case T_END_HEREDOC:   --$inString; break;
				}
			}
			else if ($inString & 1) 
			{
				switch ($token) 
				{
					case '`':
					case '"': --$inString; break;
				}
			}
			else {
				switch ($token) 
				{
					case '`':
					case '"': ++$inString; break;
	 
					case '{': ++$braces; break;
					case '}':
						if ($inString) 
						{
							--$inString;
						}
						else {
							--$braces;
							if ($braces < 0) 
							{
								throw new Exception('Braces problem!');
							}
						}
					break;
				}
			}
		}
		 
		// расхождение в открывающих-закрывающих фигурных скобках
		if ($braces) 
		{
			throw new Exception('Braces problem!');
		}
		 
		$res = false;
		 
		// второй этап проверки
		ob_start();		
		$res = eval('if (0) {?>'.$code.' <?php }; return true;');
		$error_text = ob_get_clean();
		 
		// устранение ошибки 500 в функции eval(), при директиве display_errors = off;
		header('HTTP/1.0 200 OK');
		 
		if (!$res) 
		{
			throw new Exception($error_text);
		}
		 
		return true;
	}
