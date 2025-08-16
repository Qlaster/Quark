<?php

//Hidden error "Undefined array key"
//For others who come across this problem, here is a more complete way of doing this, which preserves the error message, but demotes it back to a notice.

if (PHP_VERSION_ID > 80000)
{
	set_error_handler(function($errno, $error)
	{
		$ignore =	[
						'Trying to access array offset on value of type null',
						'Undefined array key'
					];

		foreach ($ignore as $_errorText)
			if (str_starts_with($error,  $_errorText)) return true;

		return false;
		//~ return str_starts_with($error, 'Undefined array key');
	}, E_WARNING);
}
