<?php


	//~ $_POST['pageServiceField']['url'] = $URL = trim($_POST['pageServiceField']['url']);


	//Забираем служебные теги и Формируем страницу
	//~ $page = $_POST['pageServiceField'];
	//~ $page['url'] = trim($page['url']);
	//Удаляем, что бы не мешались
	//~ unset($_POST['pageServiceField']);

	$page = $_POST;
	$page['url'] = trim($page['url']);

	//~ print_r($_POST); die;


	//~ $page['url']	= trim($_POST['URL']);
	//~ $page['html']	= $_POST['template_file'];
	//~ $page['public'] = $_POST['check_public'];
	//~ $page['sitemap']= $_POST['check_sitemap'];
	//~ $page['index']	= $_POST['check_index'];
	//~ $page['lang']	= $_POST['LANG'];

	//~ unset($_POST['URL']);
	//~ unset($_POST['template_file']);
	//~ unset($_POST['check_public']);
	//~ unset($_POST['check_sitemap']);
	//~ unset($_POST['check_index']);
	//~ unset($_POST['INDEX']);

	//Компилируем содержимое страницы
	foreach ($_POST['content'] as $tag => $value)
	{
		//~ print_r($value);
		switch ($tag[0])
		{
			//~ case "!":
				//~ $tag_name = substr($tag, 1);
				//~ $content[$tag_name]['hidden'] = $value ? 'checked' : null;
			case "~":
				$tag_name = substr($tag, 1);
				$buffer = explode(':', $value['data']);
				$content[$tag_name]['name'] = $tag_name;
				$content[$tag_name]['type'] = 'object';
				$content[$tag_name]['data']	= $buffer[1].':'.$buffer[2];
				$content[$tag_name]['hidden'] = $value['hidden'] ? 'checked' : null;
				//~ if (isset($buffer[1]) and isset($buffer[2]))
				//~ {
					//~ $content[$tag_name]['collection'] = base64_decode($buffer[1]);
					//~ $content[$tag_name]['object'] = base64_decode($buffer[2]);
				//~ }
				break;
			case "=":
				if ( $value['data'] != '' )
				{
					$hidden = $content[$tag_name]['hidden']; //TODO:отрефакторить

					$tag_name = substr($tag, 1);
					$content[$tag_name]['name'] = $tag_name;
					//$buffer = explode(':', $value);
					$content[$tag_name]['type'] = 'source';
					$content[$tag_name]['data'] = $value['data'];
					$content[$tag_name]['hidden'] = $hidden; //TODO:отрефакторить
					//~ $content[$tag_name]['hidden'] = $value['hidden'] ? 'checked' : null;
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

	//~ print_r($page); die;

	if (!$APP->page->set($page['url'], $page))
		trigger_error ( "Не удается сохранить страницу" , E_USER_WARNING );

	$controllersDir = $APP->controller->config['folder'];
	$APP->page->sitemap("$controllersDir/sitemap.xml.php");

	//Если указано, куда нужно перенаправить, то исполняем, если нет - возвращаем на предыдущую
	$referer = $_GET['referer'] ? $_GET['referer'] : $APP->url->home().'admin/content/page/edit?url='.urlencode($page['url']);
	//~ $referer = $_GET['referer'] ? $_GET['referer'] : $_SERVER['HTTP_REFERER'];

	header("Location: $referer");
