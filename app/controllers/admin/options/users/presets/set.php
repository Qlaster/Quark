<?php

	//~ print_r($_POST);

	$presets = $APP->user->presets->get();
	$presets[$_GET['name']] = $_POST['denied'];
	echo $APP->user->presets->set($presets) ? "Success" : "Error";
