<?php
	error_reporting(E_ALL & ~E_NOTICE);

	if (!$_SERVER['HTTP_REFERER'])
		$_SERVER['HTTP_REFERER'] = 'admin/content/page';


	$APP->page->clear($_GET['url']);

	header("Location: ".$_SERVER['HTTP_REFERER']);
