<?php


	$referer = $_GET['referer'] ?? 'admin/';

	//Если нам передали параметры для авторизации, то проверяем
	if (isset($_POST['login']) and isset($_POST['password']))
	{
		if ($APP->user->login($_POST['login'], $_POST['password']))
		{

			header('Location: '.$APP->url->home().$referer);
		}
		else
		{
			$content['message'] = 'Authorization failed';
		}
	}



	$content['title'] = 'Авторизация';
	$content['form']['authorization']['head'] = $APP->objects->collection('admin')->get('about')['head'] ?? '';
	$content['form']['authorization']['action'] = $referer ? 'admin/login?referer='.$referer : 'admin/login';
	$content['poster']['link'] = 'public/images/poster.png';





	//~ $themelink = $APP->url->home()."views/admin/";
	//~ $APP->template->file('admin/login.html')->themelink($themelink)->display($content);
	//~ $APP->template->base_html = $APP->url->home();
	$APP->template->file('admin/login.html')->display($content);
