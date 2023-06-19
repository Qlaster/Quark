<?php

		http_response_code(404);
		$page = $APP->page->get('error:404');
		//Передадим контроллеру на отрисовку
		$APP->controller->run('index', ['APP'=>$APP, 'page'=>$page]);
