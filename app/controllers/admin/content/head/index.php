<?php


	$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);

	$content['title'] = 'Управление заголовками страниц';
	$content['code']['head'] = $APP->page->meta->select(['urlmask'=>'','name'=>'headcode'])[0]['data'];

	$APP->template->file('admin/content/page.head.html')->display($content);

