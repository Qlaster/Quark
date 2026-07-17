<?php

	$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);

    $old = $APP->files->jailPath($_GET['old_name'] ?? '');
    $new = $APP->files->jailPath($_GET['new_name'] ?? '');
    if (!$old or !$new) exit('Путь вне разрешённой директории');

    if ( rename($old, $new) )
    {
		echo $_GET['new_name'];
		exit;
	}

	exit;
