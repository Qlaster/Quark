<?php

namespace QyberTech\Console\Commands;


/*
 * Console run .php file
 *
 */



class Run
{

	public $config = [];
	protected $app;

	function __construct($config, $app=null)
	{
			$this->config = $config;
			$this->app    = $app;
			$this->tools  = new class {use QconsoleTools;};
	}

	function run(...$args)
	{
		$args = current($args);
		$APP = $this->app;
		$runfile = getcwd() . DIRECTORY_SEPARATOR .$args[0];
		$controller = $args[0];

		//~ $APP->controller->run($controller, ['APP'=>$APP]);
		//~ exit($controllers);

		//Дополняем расширение
		$runfile  = is_file($runfile.'.php') ? $runfile.'.php' : $runfile;


		return include($runfile);
	}

}

