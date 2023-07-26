<?php

		http_response_code(404);
		//Передадим контроллеру на отрисовку
		if ($page = $APP->page->get('error:404'))
			$APP->controller->run('index', ['APP'=>$APP, 'page'=>$page]);
