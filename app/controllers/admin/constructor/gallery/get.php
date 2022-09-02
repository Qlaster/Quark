<?php
	
	$content = $APP->controller->run('admin/autoinclude', $APP);
	
	//~ echo $_GET['name']; die;
	echo json_encode($APP->object->collection('gallery')->get(urldecode($_GET['name'])));
	
	
	
	die;
