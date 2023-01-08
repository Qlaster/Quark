<?php
	header('Content-Type: application/json');

	$presets = $APP->user->presets->get();
    $result = $_GET['name'] ? $presets[$_GET['name']] : $presets;
	echo json_encode($result);
