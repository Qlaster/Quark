<?php

	$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);

    if ( rename($_GET['old_name'], $_GET['new_name']) )
    {
		echo $_GET['new_name'];
		exit;
	}

	exit;
