<?php

	$content = $APP->controller->run('admin/autoinclude', $APP);
	

	$APP->template->file('admin/constructor/object/object_edit.html')->display($content);
