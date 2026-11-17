<?php

	if (!$APP->user->role->rename($_GET['name'], $_POST['name'])) http_response_code(400);
