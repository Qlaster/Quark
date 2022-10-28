<?php
	error_reporting(E_ALL);

	if ($facade = $_GET['facade']) $APP->$facade;
