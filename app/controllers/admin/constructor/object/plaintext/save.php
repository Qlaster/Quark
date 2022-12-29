<?php

	try
	{
		$object = $APP->config->fromString($_POST['config']['body']);
	}
	catch (Error $e)
	{
		echo $e->getMessage();
		exit;
	}

	echo ($APP->objects->collection($_GET['collection'])->set($_GET['object'], $object)) ? 'Сохранение успешно' : 'Не удалось сохранить объект';



