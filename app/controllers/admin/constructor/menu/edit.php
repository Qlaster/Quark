<?php

	$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);



	$APP->template->file('admin/constructor/menu/menu.edit.html')->display($content);
