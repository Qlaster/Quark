<?php

		http_response_code(404);
		//~ $page = $APP->page->get('error:500');
		$content['title'] = 'Страница не найдена';
		$content['code']  = 404;
		$content['head']  = 'Страница не найдена';
		//~ $content['text']  = $error->getMessage(). ' in line '.$error->getLine();
		//~ $content['text']  .= '<br>'.$error->getFile();
		//~ $APP->template->file($page['html'])->display($content);
		$APP->template->file('error/400/404.htm')->display($content);



		//~ http_response_code(404);
		//~ //Передадим контроллеру на отрисовку
		//~ if ($page = $APP->page->get('error:404'))
			//~ $APP->controller->run('index', ['APP'=>$APP, 'page'=>$page]);
