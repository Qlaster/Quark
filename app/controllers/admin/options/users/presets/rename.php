<?php

	if (!$APP->user->preset->rename($_GET['name'], $_POST['name'])) http_response_code(400);
