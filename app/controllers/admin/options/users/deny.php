<?php

	$content['title'] = "Ограничение доступа";
	$content['head'] = "500";
	$content['text'] = "Доступ к данной странице ограничен политикой прав";

	$APP->template->file('admin/error.html')->display($content);

	
