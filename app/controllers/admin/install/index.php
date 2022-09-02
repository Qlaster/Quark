<?php

	//Процесс инсталяции
	


	//Получаем меню админки
	$menu = include 'main_menu.php';
	//Кладем его в каталог объектов
	$APP->object->collection('admin')->set('mainmenu', $menu);
	
	$menu = include 'profile_menu.php';
	//Кладем его в каталог объектов
	$APP->object->collection('admin')->set('profilemenu', $menu);




	
	print_r($menu);
