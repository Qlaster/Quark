<?php

	$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);

	$content['object'] = $_GET['object'];

	$APP->template->file('admin/constructor/object/object_edit.html')->display($content);
