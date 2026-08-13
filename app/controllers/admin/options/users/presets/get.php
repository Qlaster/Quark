<?php
	header('Content-Type: application/json');

	$result = $_GET['name'] ? $APP->user->preset->get($_GET['name']) : $APP->user->preset->get();
	echo json_encode($result);
