<?php


	$APP->user->logout();
	$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);
