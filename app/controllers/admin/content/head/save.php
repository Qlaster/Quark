<?php


	$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);
	$result = $APP->page->meta->replace(['urlmask'=>'','name'=>'headcode','data'=>$_POST['headcode']]) ? 'OK' : ('Возникла ошибка' and http_response_code(500));

	echo $result;

