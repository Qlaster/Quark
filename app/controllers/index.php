<?php

	$url = $APP->url->page();

	//Загрузим страницу из базы, если нам ее не передали
	if (!isset($page))
		$page = $APP->page->get($url);

	//Если не нашли такой страницы - подгрузим заглушку
	if (($page === null) or (!$page['public']))
	{
		$errorController = $APP->route->match('404', ['error'])[0];
		http_response_code(404);
		if (! (isset($errorController) or is_readable($errorController)))
			throw new ErrorException("Quark: Error controller '$errorController' not found", 500);
		$APP->controller->run($errorController, ['APP'=>$APP]);
	}

	//Страница существует?
	if (($page != null) and ($page['public']))
	{

		if (! isset($page['content'])) $page['content'] = array();
		$content = array();

		//да, страница существует - осуществляем небольшую предподготовку
		foreach ($page['content'] as $name => &$tag)
		{
			//Скрытые элементы показывать не будем
			if ($tag['hidden']) continue;
			switch ($tag['type'])
			{
				case 'text':
						$content[$name] = $tag['data'];
					break;
				case 'object':
						list ($collection, $object) = explode(':', $tag['data']);
						$collection = base64_decode($collection);
						$object = base64_decode($object);

						$tag_name = explode(':', $tag['name']);
						$tag_name[0] = base64_decode($tag_name[0]);
						$tag_name[1] = base64_decode($tag_name[1]);
						$content[$tag_name[0]][$tag_name[1]] = $APP->objects->collection($collection)->get($object);

					break;
				case 'source':
						//Получаем из названгия тега его класс (гарея, меню и т.д.) и название
						list ($class, $name) = explode(':', $tag['name']);
						//Декодируем
						$class = base64_decode($class);
						$name = base64_decode($name);
						//Получим контент от провайдера данных
						$content[$class][$name] = $APP->provider->execute($tag['data'], ['APP'=>$APP]);
						//Строим объект контента
						//~ $content[$class][$name] = $APP->controller->run($tag['data'], $APP);
					break;
				default:

			}

		}

		//Пометим относительную директорию, что бы шаблонизатор мог указать его в шаблоне
		$APP->template->base_html = $APP->url->home();

		//Добавим заголовок страницы
		$APP->template->head = $APP->page->meta->get(['urlmask'=>'','name'=>'headcode']);

		//выводим используя встроенный шаблонизатор
		$APP->template->file($page['html'])->display($content);
	}
	else
	{
		//Нет, страницы не нашли
		$content = array();
		http_response_code(404);
	}



