<?php

namespace QyberTech\Console\Traits;


define('P_ERROR', E_ERROR);
define('P_TITLE', E_WARNING);
define('P_H1',    E_STRINCT);
define('P_H2',    E_NOTICE);





trait QPrint
{
	public $widthTterminal = 70;

	function print($line='', $type=null)
	{
		if (!$type)
			return print($line.PHP_EOL);

		switch ($type)
		{
			case E_ERROR:
				$border['top']    = '!';
				$border['bottom'] = '!';
				break;
			case E_WARNING:
				$border['top']    = '=';
				$border['bottom'] = '=';
				break;
			case E_STRINCT:
				$border['top']    = '-';
				$border['bottom'] = '-';
				break;
			case E_NOTICE:
				$border['top']    = '';
				$border['bottom'] = '-';
				break;
			default:
				$border['top']    = '';
				$border['bottom'] = '';
		}

		$widthTterminal = $this->widthTterminal;
		$tabulator = ($widthTterminal - mb_strlen($line))/2;
		if ($tabulator < 0) $tabulator = 0;

		$this->print();
		$this->print(str_repeat($border['top'],    $widthTterminal));
		$this->print(str_repeat(' ', $tabulator).$line);
		$this->print(str_repeat($border['bottom'], $widthTterminal));
	}

}
