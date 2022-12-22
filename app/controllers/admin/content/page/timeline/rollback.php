<?php
	error_reporting(E_ALL & ~E_NOTICE);

	if (!$_SERVER['HTTP_REFERER'])
		$_SERVER['HTTP_REFERER'] = 'admin/content/page/timeline?url='.$_GET['url'];


	$APP->page->back($_GET['url'], $_GET['lang'], $_GET['version']);

	header("Location: ".$_SERVER['HTTP_REFERER']);
