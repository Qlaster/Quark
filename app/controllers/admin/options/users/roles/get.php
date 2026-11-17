<?php
	header('Content-Type: application/json');

	$result = $_GET['name'] ? $APP->user->role->get($_GET['name']) : $APP->user->role->get();
	echo json_encode($result);
