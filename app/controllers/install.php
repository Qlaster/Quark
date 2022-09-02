<?php
	
	lib('utils/simple_gallery');

	$config = $APP->config->get();
	
	//~ print_r($config); die;

		
	foreach ($config as $section => $objects) 
	{
		foreach ($objects as $name => $object) 
		{
			echo $section.'|'.$name.'<br>';
			$APP->object->collection($section)->set($name, $object);
		}
	}

	echo 1;
	//~ $photo = Simple_Gallery::Albums('public/photo');
	
	
	//~ $user['login'] = 'vl';
	//~ $user['password'] = '0';
	//~ $user = $APP->user->add($user);
	
	

	
	//~ $APP->object->collection('gallery')->set('main_photo',  array_shift($photo));
