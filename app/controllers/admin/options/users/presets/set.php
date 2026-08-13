<?php

	echo $APP->user->preset->set($_GET['name'], $_POST['denied']) ? "Success" : "Error";
