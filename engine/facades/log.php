<?php


		
	
	class Q_Logger
	{
		public $config = array();
		
		
		public function __construct($file=null)
		{
			$this->config['enable'] = true;
			$this->config['unixtime'] = false;
			$this->config['appender']['datepattern'] = 'Y-m-d';
			$this->config['appender']['timepattern'] = 'Y-m-d';
			$this->config['dir'] = 'temp/log';
		}
		
		
		public function trace($message, $source=null)   // Not logged because TRACE < WARN
		{
			return $this->add($message, 'TRACE', $source);
		}
		
		
		public function debug($message, $source=null)  // Not logged because DEBUG < WARN
		{
			return $this->add($message, 'DEBUG', $source);
		}
		
		
		public function info($message, $source=null)    // Not logged because INFO < WARN
		{
			return $this->add($message, 'INFO', $source);
		}
	
	
		public function warn($message, $source=null)   // Logged because WARN >= WARN
		{
			return $this->add($message, 'WARNING', $source);
		}

		public function error($message, $source=null)   // Logged because ERROR >= WARN
		{
			return $this->add($message, 'ERROR', $source);
		}
		
		
		public function fatal($message, $source=null)  // Logged because FATAL >= WARN
		{			
			return $this->add($message, 'FATAL', $source);
		}
		
		
		/*
		 * 
		 * name: Запись в лог
		 * @param
		 * @return
		 * 
		 */
		public function add($message, $label, $source)
		{
			if ((!$message) or (!$this->config['enable'])) return false;
			
			$label = str_replace('[', '_', $label);
			$label = str_replace(']', '_', $label);
			
			$message = str_replace("\n", ' ', $message);
			$message = str_replace("\r", ' ', $message);			
			
			if (!$source)
			{
				$source = debug_backtrace();	
				$source = $source[0]['class'];
			}
			//$date = date('Y-m-d');						
			//$time = date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME']);
			
			$time = $this->time("Y-m-d H:i:s,");	
			//~ $time = date("Y-m-d H:i:s");		
			List($date, $time) = explode(' ', $time);

			//$date = 'sacacsa';						
			//$time = time();
			
			$logfile = $this->config['dir']."/$date.log";
			$logdir = dirname($logfile);			
			
			if (! file_exists($logdir))	mkdir($logdir);
			
			//$message = 
			//18:43:39,545 [5428] INFO Foo - We have liftoff.
			return file_put_contents($logfile, "$time [$source] $label - ".$message."\r\n", FILE_APPEND | LOCK_EX );
		}
		
		/*
		 * 
		 * name: Возвращает время с микросекундами
		 * @param
		 * @return
		 * 
		 */
		public function time($timeformat="H:i:s,")
		{
			//~ return '2013.23.23 13:21:23';
			//~ return date($timeformat);
			$unixtime = microtime(TRUE);
			$u = explode('.', number_format($unixtime, 4));			
			$u = $u[1];
			
			//$u = $u[1];
			//return time();
			//return date('H:i:s', time());
			
		
			//TODO:Следует переписать функцию date, так как она очень медлительна
			return date($timeformat, $unixtime).$u;
		}
	}
	
	
	
	
	return new Q_Logger;
