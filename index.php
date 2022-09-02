<?php
		
	# ---------------------------------------------------------------- #
	#             Объявление автозагрузки (стандарт PSR4)              #
	# ---------------------------------------------------------------- #				
	//Подключаем файл с окружением ядра
	include "engine/core/core.php";


	# ---------------------------------------------------------------- #
	#                     Инициализация приложения                     #
	# ---------------------------------------------------------------- #
	//Создаем приложение (указав диретории размещения расширений ядра, фасадов, моделей и библиотек)
	$APP = new APP('engine/core', ['app'=>'engine/facades', 'models'=>'app/models'], 'engine/vendor');
	//Загрузим переменные окружения
	$APP->config->loadENV('.env', 'app/.env');
	//Симлинк на новый модуль
	$APP->object = $APP->objects; 


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
	if ($APP->controller->run($rout, $APP) === null) 
	{
		//Контроллера нет? Что ж... Попробуем запустить и передать управление стандартному index контроллеру
		if ( $APP->controller->run($APP->controller->config['handler'], $APP) === null )  http_response_code(404);
	}
		

