<?php

	$page = $_POST;
	$page['url'] = trim($page['url']);



	//Компилируем содержимое страницы
	foreach ($page['content'] as $tag => $value)
	{
		//Скрытые элементы показывать не будем
		if ($value['hidden']) continue;

		switch ($tag[0])
		{
			case "~":
				$tag_name = substr($tag, 1);
				$buffer = explode(':', $value['data']);
				$content[$tag_name]['name'] = $tag_name;
				$content[$tag_name]['type'] = 'object';
				$content[$tag_name]['data']	= $buffer[1].':'.$buffer[2];
				$content[$tag_name]['hidden'] = $value['hidden'] ? 'checked' : null;
				break;
			case "=":
				if ( $value['data'] != '' )
				{
					$hidden = $content[$tag_name]['hidden']; //TODO:отрефакторить
					$tag_name = substr($tag, 1);
					$content[$tag_name]['name'] = $tag_name;
					$content[$tag_name]['type'] = 'source';
					$content[$tag_name]['data'] = $value['data'];
					$content[$tag_name]['hidden'] = $hidden; //TODO:отрефакторить
				}

				break;
			default:
				$content[$tag]['name'] = $tag;
				$content[$tag]['data'] = $value['data'];
				$content[$tag]['type'] = 'text';
				$content[$tag]['hidden'] = $value['hidden'] ? 'checked' : null;
		}
	}


	$page['content'] = $content;

	//Запустим контроллер рендеринга страницы
	$APP->controller->run('index', ['APP'=>$APP, 'page'=>$page]);
