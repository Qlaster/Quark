<?php

	http_response_code(500);
	$page = $APP->page->get('error:500');
	$content['title'] = $page['content']['title']['data'];
	$content['code']  = $page['content']['code']['data'];
	$content['head']  = $page['content']['head']['data'];
	$content['text']  = $e->getMessage(). ' in line '.$e->getLine();
	$content['text']  .= '<br>'.$e->getFile();
	$APP->template->file($page['html'])->display($content);
