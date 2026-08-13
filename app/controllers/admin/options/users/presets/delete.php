<?php

	echo $APP->user->preset->delete($_POST['name']) ? "Success" : "Error";
