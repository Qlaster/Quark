<?php

	//error_reporting(E_ALL & ~E_NOTICE);

	
		
	//Забираем служебные теги и Формируем страницу
	$article['name'] = $_POST['name'];
	$article['head'] = $_POST['head'];
	$article['tag'] = $_POST['tag'];
	$article['section'] = $_POST['section'];
	$article['language'] = $_POST['language'];
	$article['public'] = $_POST['public'];	
	$article['text'] = $_POST['text'];

	//Удаляем, что бы не мешались
	unset($_POST['name']);
	unset($_POST['head']);
	unset($_POST['tag']);
	unset($_POST['section']);
	unset($_POST['language']);
	unset($_POST['public']);
	unset($_POST['text']);

	
		
	$APP->object->collection('article')->set($article['name'], $article);

	print_r($APP->object->collection('article')->get($article['name']));

	// $APP->object->collection('article')->set($article['name'], $page);