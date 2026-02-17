<?php

	echo $APP->user->presets->delete($_POST['name']) ? "Success" : "Error";
