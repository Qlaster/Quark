<?php

	/**
	 * Describes a logger instance.
	 *
	 * The message MUST be a string or object implementing __toString().
	 *
	 * The message MAY contain placeholders in the form: {foo} where foo
	 * will be replaced by the context data in key "foo".
	 *
	 * The context array can contain arbitrary data, the only assumption that
	 * can be made by implementors is that if an Exception instance is given
	 * to produce a stack trace, it MUST be in a key named "exception".
	 *
	 * See https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md
	 * for the full interface specification.
	 */

	namespace App\Facade;

	# ---------------------------------------------------------------- #
	#                 ЭКСПОРТИРУЕМ 	ИНТЕРФЕЙС                          #
	# ---------------------------------------------------------------- #
	interface QLoggerInterface
	{
		/**
		 * System is unusable.
		 *
		 * @param string $message
		 * @param array $context
		 * @return bool result
		 */
		public function emergency($message, array $context = array());

		/**
		 * Action must be taken immediately.
		 *
		 * Example: Entire website down, database unavailable, etc. This should
		 * trigger the SMS alerts and wake you up.
		 *
		 * @param string $message
		 * @param array $context
		 * @return bool result
		 */
		public function alert($message, array $context = array());

		/**
		 * Critical conditions.
		 *
		 * Example: Application component unavailable, unexpected exception.
		 *
		 * @param string $message
		 * @param array $context
		 * @return bool result
		 */
		public function critical($message, array $context = array());

		/**
		 * Runtime errors that do not require immediate action but should typically
		 * be logged and monitored.
		 *
		 * @param string $message
		 * @param array $context
		 * @return bool result
		 */
		public function error($message, array $context = array());

		/**
		 * Exceptional occurrences that are not errors.
		 *
		 * Example: Use of deprecated APIs, poor use of an API, undesirable things
		 * that are not necessarily wrong.
		 *
		 * @param string $message
		 * @param array $context
		 * @return bool result
		 */
		public function warning($message, array $context = array());

		/**
		 * Normal but significant events.
		 *
		 * @param string $message
		 * @param array $context
		 * @return bool result
		 */
		public function notice($message, array $context = array());

		/**
		 * Interesting events.
		 *
		 * Example: User logs in, SQL logs.
		 *
		 * @param string $message
		 * @param array $context
		 * @return bool result
		 */
		public function info($message, array $context = array());

		/**
		 * Detailed debug information.
		 *
		 * @param string $message
		 * @param array $context
		 * @return bool result
		 */
		public function debug($message, array $context = array());

		/**
		 * Logs with an arbitrary level.
		 *
		 * @param mixed $level
		 * @param string $message
		 * @param array $context
		 * @return bool result
		 */
		public function log($level, $message, array $context = array());
	}


	class Qlogger
	{
		public $config = [];

		public function __construct($config = [])
		{
			$this->config['enable']				= true;
			$this->config['dir']				= 'app/log/';
			$this->config['pattern']['date']	= 'H:i:s.u';
			$this->config['pattern']['file']	= 'Y-m-d';


		}

		public function log($level, $message, array $context = [])
		{
			if ((!$message) or (!$this->config['enable'])) return false;

			$level = str_replace('[', '_', $level);
			$level = str_replace(']', '_', $level);

			$message = str_replace("\n", ' ', $message);
			$message = str_replace("\r", ' ', $message);


			$dateFormatted = (new \DateTime())->format('Y-m-d H:i:s');

			// Преобразуем $context в формат json
			$contextString = json_encode($context);
			$message = sprintf(
					'[%s] %s: %s %s%s',
					$dateFormatted,
					$level,
					$message,
					$contextString, // Добавляем контекст к строке лога
					PHP_EOL
				);

			$logfile = $this->config['dir'].DIRECTORY_SEPARATOR."$date.log";
			$logdir  = dirname($logfile);

			if ($logdir && !file_exists($logdir)) mkdir($logdir);

			file_put_contents($logfile, $message, FILE_APPEND);
		}
	}




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


		public function warning($message, $source=null)   // Logged because WARN >= WARN
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
