<?php
	// Report all PHP errors
	error_reporting(E_ALL & ~E_NOTICE);
	# ---------------------------------------------------------------- #
	#               Инициализация переменных окружения                 #
	# ---------------------------------------------------------------- #
    $_ENV = array_merge($_ENV, parse_ini_file(".env", true));


	# ---------------------------------------------------------------- #
	#             Объявление автозагрузки (стандарт PSR4)              #
	# ---------------------------------------------------------------- #
	//Подключаем файл с окружением ядра
	//~ include "engine/core/core.php";
	include $_ENV['core']['path']."core.php";


	# ---------------------------------------------------------------- #
	#                     Инициализация приложения                     #
	# ---------------------------------------------------------------- #
	//Создаем приложение (указав диретории размещения расширений ядра, фасадов, моделей и библиотек)
	//~ $APP = new APP('engine/core', ['app'=>'engine/facades', 'models'=>'app/models'], 'engine/vendor');
	$APP = new APP($_ENV['core']['path'], $_ENV['facades'], $_ENV['vendor']);
	//Загрузим переменные окружения
	$APP->config->loadENV('.env', 'app/.env');
	//Записываем визит (регистрируется вызов после завершения работы app)
	$APP->visits->push($APP->user->logged()['login']);


	# ---------------------------------------------------------------- #
	#            Обработка псевдонимов адресов страниц                 #
	# ---------------------------------------------------------------- #
	if ($alias = $APP->route->match($APP->url->page(), ['alias']))
		$APP->url->page = $alias[0];


	# ---------------------------------------------------------------- #
	#            Обработка правил маршрутеризации (роутинг)            #
	# ---------------------------------------------------------------- #
	$controllers = $APP->route->match($APP->url->page());


	# ---------------------------------------------------------------- #
	#        Передача управления контроллеру (на основе правил)        #
	# ---------------------------------------------------------------- #
	try
	{
		//Последовательно исполняем контроллеры, указанные в правилах
		foreach ($controllers as $action)
		{
			if ($ctrlResponse = $APP->controller->run($action, ['APP'=>$APP]) === null)
			{
				//Контроллера нет? Что ж... Попробуем запустить и передать управление стандартному index контроллеру
				if ($ctrlResponse = $APP->controller->run($APP->controller->config['handler'], ['APP'=>$APP]) === null )
				{
					throw new ErrorException('Default controller '.$APP->controller->config['handler'].' not found', 500);
				}
			}
		}


		/*
		if ($ctrlResponse = $APP->controller->run($rout, ['APP'=>$APP]) === null)
		{
			//Контроллера нет? Что ж... Попробуем запустить и передать управление стандартному index контроллеру
			if ($ctrlResponse = $APP->controller->run($APP->controller->config['handler'], ['APP'=>$APP]) === null )
			{
				throw new ErrorException('Default controller '.$APP->controller->config['handler'].' not found', 500);
				// http_response_code(500);
				// $page = $APP->page->get('error:500');
				// $APP->template->file($page['html'])->display($page['content']);
			}
		}
		*/
	}
	catch (Error $e)
	{
		http_response_code(500);
		$page = $APP->page->get('error:500');
		$content['title'] = $page['content']['title']['data'];
		$content['code']  = $page['content']['code']['data'];
		$content['head']  = $page['content']['head']['data'];
		$content['text']  = $e->getMessage(). ' in line '.$e->getLine();
		$content['text']  .= '<br>'.$e->getFile();
		$APP->template->file($page['html'])->display($content);
	}

	//~ echo "<!---".$APP->utils->runtime()."-->";
