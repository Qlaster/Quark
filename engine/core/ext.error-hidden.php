<?php

//Hidden error "Undefined array key"
//For others who come across this problem, here is a more complete way of doing this, which preserves the error message, but demotes it back to a notice.

if (PHP_VERSION_ID > 80000)
{
	set_error_handler(function($errno, $error, $errfile, $errline)
	{
		$ignore =	[
						'Trying to access array offset on value of type null',
						'Trying to access array offset on null',
						'Creation of dynamic property',
						'Undefined array key',
						'Undefined variable',
						'Passing null to parameter' //Deprecated: func(): Passing null to parameter #N (PHP 8.1+)
					];

		foreach ($ignore as $_errorText)
			if (strpos($error,  $_errorText) !== false) return true;

		return false;
	}, E_WARNING | E_DEPRECATED);
}
