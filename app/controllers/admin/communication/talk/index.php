<?php


	$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);

	$content['title'] = 'Коммуникации';
	//~ $content['catalogs']['list']  = $APP->catalog->listing();





	$APP->template->file('admin/communication/talk/channels.html')->display($content);

