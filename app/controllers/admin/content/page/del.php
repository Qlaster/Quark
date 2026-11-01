<?php

	$APP->page->del($_GET['url'], $_GET['lang'] ?? null, $_GET['version'] ?? null);
	header('Location: index');
