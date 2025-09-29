<?php


	$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);

	$content['title'] = 'Медиафайлы';
	//~ $content['code']['head'] = $APP->page->meta->select(['urlmask'=>'','name'=>'headcode'])[0]['data'] ?? '';

	$APP->template->file('admin/content/mediafiles/media.html')->display($content);

