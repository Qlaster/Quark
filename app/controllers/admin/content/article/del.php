<?php
	$APP->object->collection('article')->del($_GET['url']);
	header('Location: index');
