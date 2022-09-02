<?php

	$APP->page->del($_GET['url'], $_GET['lang'], $_GET['version']);
	header('Location: index');
