<?php

	$content = $APP->controller->run('admin/autoinclude', $APP);
	
	

	$APP->template->file('admin/constructor/menu_edit.html')->display($content);
