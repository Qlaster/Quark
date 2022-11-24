<?php
	error_reporting(E_ALL);

	$content = $APP->controller->run('admin/autoinclude', $APP);

	//Получаем список модулей (фасадов и моделей)
	$providerPath = $APP->provider->config['folder'];
	$providerList = $APP->provider->listing();

	//~ $units_path = glob($APP->core_config['path_units']['app']."/*.php");


	//Получаем базовое имя модуля
	foreach ($providerList as $key => &$unitfile)
	{
		$content['units']['list'][$key]['name'] = $unitfile;

		$unitfile = $providerPath .DIRECTORY_SEPARATOR. $unitfile;

		$content['units']['list'][$key]['file']		= str_replace('/', ' / ',  $unitfile);
		$content['units']['list'][$key]['alias']	= basename($unitfile, '.php');
		$content['units']['list'][$key]['create']	= date('d.m.Y H:i:s', filemtime($unitfile));
		$content['units']['list'][$key]['size']		= round(filesize($unitfile)/1024, 2) . ' Kb';


		$content['units']['list'][$key]['link']['config']	= "admin/app/codeeditor/?config=".unit_config_file($unitfile);
		$content['units']['list'][$key]['link']['code']		= "admin/app/codeeditor/?file=$unitfile";

		$content['units']['list'][$key]['md5'] = md5($unitfile);
		//~ $content['units']['list'][$key]['testlink'] = "admin/components/inittest?facade=".$content['units']['list'][$key]['alias'];

		$content['units']['list'][$key]['analize']['status'] = 'Без анализа';
		if ($_GET['analize'])
		{
			$starttime = microtime(true);
			$alias = $content['units']['list'][$key]['alias'];
			$content['units']['list'][$key]['analize']['syntax'] = $APP->controller->check($unitfile);

			//~ try
			//~ {
				//~ //Инициализируем модуль
				//~ $interface = $APP->$alias;
				//~ $content['units']['list'][$key]['analize']['init'] = true;
			//~ }
			//~ catch (Error $e)
			//~ {
				//~ $content['units']['list'][$key]['analize']['init'] = false;
			//~ }
			if ($content['units']['list'][$key]['analize']['syntax'])
				$content['units']['list'][$key]['analize']['init'] = true;

			$content['units']['list'][$key]['analize']['runtime'] = round(microtime(true) - $starttime, 4);
			$times[] = $content['units']['list'][$key]['analize']['runtime'];

			$content['units']['list'][$key]['analize']['check'] = $content['units']['list'][$key]['analize']['syntax'] && $content['units']['list'][$key]['analize']['init'];
			$content['units']['list'][$key]['analize']['status'] = 'Готов';
			$content['units']['list'][$key]['analize']['init'] ?: $content['units']['list'][$key]['analize']['status'] = 'Инициализация';
			$content['units']['list'][$key]['analize']['syntax'] ?: $content['units']['list'][$key]['analize']['status'] = 'Синтаксис';


			//~ $content['units']['list'][$key]['analize']['init']		= $APP->controller->check($unitfile);
			//~ $content['units']['list'][$key]['syntax']	= php_check_syntax($unitfile, $buffer);
		}
	}

	//Рассчитаем предполагаемое влияние на загрузку
	if ($_GET['analize'])
	{
		$min = min($times);
		$max = max($times);
		$per = ($max-$min)/100;

		foreach ($content['units']['list'] as &$_unit)
			$_unit['analize']['timeload'] = round( ($_unit['analize']['runtime']-$min)/$per );
	}

	//~ print_r($content['units']['list']); die;

	$content['title'] = 'Модули системы';
	$content['button']['analize']['head'] = 'Анализ провайдеров';
	$content['button']['analize']['link'] = $APP->url->page()."?analize=1";




	$APP->template->file('admin/components/units.html')->display($content);


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
