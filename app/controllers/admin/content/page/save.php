<?php


	//~ $_POST['pageServiceField']['url'] = $URL = trim($_POST['pageServiceField']['url']);

	//Забираем служебные теги и Формируем страницу
	$page = $_POST['pageServiceField'];
	$page['url'] = trim($page['url']);
	//Удаляем, что бы не мешались
	unset($_POST['pageServiceField']);


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

	$page['content'] = $content;

	if (!$APP->page->set($page['url'], $page))
		trigger_error ( "Не удается сохранить страницу" , E_USER_WARNING );

	$APP->page->sitemap('controllers/sitemap.xml.php');

	//Если указано, куда нужно перенаправить, то исполняем, если нет - возвращаем на предыдущую
	$referer = $_GET['referer'] ? $_GET['referer'] : $_SERVER['HTTP_REFERER'];
	header("Location: $referer");
