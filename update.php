#!/usr/bin/php
<?php

	

	print_r($argv);

	echo "input#";
	while ($c = fread(STDIN, 1)) 
	{
		if (ord($c) == 10) 
		{
			echo "Enter";
			break;
		}
		
		echo $c;
		//~ echo "Read from STDIN: " . $c . "\ninput# ";
	}

	//~ system("stty -icanon");
	//~ echo "input# ";
	//~ while ($c = fread(STDIN, 1)) 
	//~ {
		
		//~ echo "Read from STDIN: " . $c . "\ninput# ";
	//~ }

	echo $c;












	function readchar($prompt)
	{
		readline_callback_handler_install($prompt, function() {});
		$char = stream_get_contents(STDIN, 1);
		readline_callback_handler_remove();
		return $char;
	}

	// example:
	if (!in_array(
		readchar('Continue? [Y/n] '), ["\n", 'y', 'Y']
		// enter/return key ("\n") for default 'Y'
	)) die("Good Bye\n");
	$name = readline("Name: ");
	echo "Hello {$name}.\n";
