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
	//Симлинк на новый модуль
	$APP->object = $APP->objects;
	//Записываем визит (регистрируется вызов после завершения скрипта)
	$APP->visits->push($APP->user->logged()['login']);


	# ---------------------------------------------------------------- #
	#            Обработка псевдонимов адресов страниц                 #
	# ---------------------------------------------------------------- #
	$alias = $APP->route->alias( $APP->url->page() );
	if ($alias !== false)
	{
		$APP->url->redirect = $APP->url->page;
		$APP->url->page     = $alias;
	}

	# ---------------------------------------------------------------- #
	#            Обработка правил маршрутеризации (роутинг)            #
	# ---------------------------------------------------------------- #
	//Проверим, есть ли правила для данного адреса...
	$rout = $APP->route->rule( $APP->url->page() );
	//Если маршрут не описан, просто закинем адрес страницы как есть
	if ($rout === false) $rout = $APP->url->page();


	# ---------------------------------------------------------------- #
	#                Обработка хуков и callback высовов                #
	# ---------------------------------------------------------------- #
	//Проверим, есть ли хуки и обработчики для данной страницы
	$hook = $APP->route->hook( $APP->url->page() );
	//Если есть обработчик - вызываем его
	//~ if ($hook !== false) $APP->controller->run($hook, $APP);
	if ($hook !== false) include("app/$hook");


	# ---------------------------------------------------------------- #
	#        Передача управления контроллеру (на основе правил)        #
	# ---------------------------------------------------------------- #
	//Правила есть. Будем исполнять тот контроллер, который указан в правилах
	try
	{
		if ($ctrlResponse = $APP->controller->run($rout, ['APP'=>$APP]) === null)
		{
			//Контроллера нет? Что ж... Попробуем запустить и передать управление стандартному index контроллеру
			if ($ctrlResponse = $APP->controller->run($APP->controller->config['handler'], ['APP'=>$APP]) === null )
			{
				throw new ErrorException('Default controller '.$APP->controller->config['handler'].' not found', 500);
				//~ http_response_code(500);
				//~ $page = $APP->page->get('error:500');
				//~ $APP->template->file($page['html'])->display($page['content']);
			}
		}
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
