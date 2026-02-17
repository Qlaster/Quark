<?php

	if (!$APP->user->presets->rename($_GET['name'], $_POST['name'])) http_response_code(400);
