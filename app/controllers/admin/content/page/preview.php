<?php

	$page = $_POST['pageServiceField'];
	$page['url'] = trim($page['url']);
	//Удаляем, что бы не мешались
	unset($_POST['pageServiceField']);


	//Компилируем содержимое страницы
	foreach ($_POST as $tag => $value)
	{
		switch ($tag[0])
		{
			case "~":
				$tag_name = substr($tag, 1);
				$buffer = explode(':', $value);
				$content[$tag_name]['name'] = $tag_name;
				$content[$tag_name]['type'] = 'object';
				$content[$tag_name]['data']	= $buffer[1].':'.$buffer[2];
				//~ if (isset($buffer[1]) and isset($buffer[2]))
				//~ {
					//~ $content[$tag_name]['collection'] = base64_decode($buffer[1]);
					//~ $content[$tag_name]['object'] = base64_decode($buffer[2]);
				//~ }
				break;
			case "=":
				if ( $value != '' )
				{
					$tag_name = substr($tag, 1);
					$content[$tag_name]['name'] = $tag_name;

					//$buffer = explode(':', $value);
					$content[$tag_name]['type'] = 'source';
					$content[$tag_name]['data'] = $value;
				}
				break;
			default:
				$content[$tag]['name'] = $tag;
				$content[$tag]['data'] = $value;
				$content[$tag]['type'] = 'text';
		}
	}

	//print_r($content); die;

	$page['content'] = $content;

	//Запустим контроллер рендеринга страницы
	$APP->controller->run('index', ['APP'=>$APP, 'page'=>$page]);
