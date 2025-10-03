<?php

	http_response_code(500);
	//~ $page = $APP->page->get('error:500');
	//~ $content['title'] = $page['content']['title']['data'];
	//~ $content['code']  = $page['content']['code']['data'];
	//~ $content['head']  = $page['content']['head']['data'];

	$content['text']  = $error->getMessage(). ' in line '.$error->getLine();
	$content['text']  .= '<br>'.$error->getFile();
	//~ $APP->template->file($page['html'])->display($content);
	$APP->template->file('error/500/500.htm')->display($content);

