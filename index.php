<?php
	// Report all PHP errors
	error_reporting(E_ALL & ~E_NOTICE);
	# ---------------------------------------------------------------- #
	#               Инициализация переменных окружения                 #
	# ---------------------------------------------------------------- #
    $_ENV = array_merge($_ENV, parse_ini_file(".env", true));
	$_SESSION[1] = 1;

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
	$APP->config->loadENV(['.env', 'app/.env']);
	//Записываем визит (регистрируется вызов после завершения работы app)

	//~ $APP->visits->push($APP->user->logged()['login']);
	//~ $APP->visits->push();
	$APP->user->logged()['login'];
	var_dump($_SESSION);
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
	}
	catch (Error $error)
	{
		$errorController = $APP->route->match('500', ['error'])[0];
		$APP->controller->run($errorController, ['APP'=>$APP, 'error'=>$error]);
	}

	//~ echo "<!---".$APP->utils->runtime()."-->";
