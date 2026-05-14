<?php

namespace App\Facade;

use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;


# ---------------------------------------------------------------- #
#                 ЭКСПОРТИРУЕМ 	ИНТЕРФЕЙС                          #
# ---------------------------------------------------------------- #
interface QLoggerInterface
{
    /** System is unusable. */
    public function emergency($message, array $context = []): void;

    /** Action must be taken immediately. */
    public function alert($message, array $context = []): void;

    /** Critical conditions. */
    public function critical($message, array $context = []): void;

    /** Runtime errors that do not require immediate action. */
    public function error($message, array $context = []): void;

    /** Exceptional occurrences that are not errors. */
    public function warning($message, array $context = []): void;

    /** Normal but significant events. */
    public function notice($message, array $context = []): void;

    /** Interesting events. */
    public function info($message, array $context = []): void;

    /** Detailed debug information. */
    public function debug($message, array $context = []): void;

    /** Logs with an arbitrary level. */
    public function log($level, $message, array $context = []): void;

    /** Returns a logger instance for the given channel. */
    public function channel(string $name);
}


# ---------------------------------------------------------------- #
#                 РЕАЛИЗАЦИЯ   ИНТЕРФЕЙСА                          #
# ---------------------------------------------------------------- #
class LogFactory implements QLoggerInterface
{
    public $config = [];
    private $loggers = [];
    private $default;

    public function __construct(array $config)
    {
        $this->config  = $config;
        $this->default = $config['default'] ?? 'app';
    }

    public function channel(string $name): self
    {
        $clone          = clone $this;
        $clone->default = $name;
        return $clone;
    }

    private function getLogger(string $name): Logger
    {

        if (!$name = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $name))
            throw new \InvalidArgumentException("Log channel name is empty after sanitization");


        if (!isset($this->loggers[$name]))
        {
            $dir   = rtrim($this->config['dir']    ?? 'app/log');
            $keep  = (int)($this->config['rotate'] ?? 30);
            $level = $this->config['channel'][$name]['level'] ?? ($this->config['level'] ?? 'debug');

            $logger = new Logger($name);
            $logger->pushHandler(new RotatingFileHandler("$dir/$name/app.log", $keep, $level));
            $logger->pushHandler(new RotatingFileHandler("$dir/app.log",       $keep, $level));

            $this->loggers[$name] = $logger;
        }
        return $this->loggers[$name];
    }

    public function emergency($message, array $context = []): void   { $this->getLogger($this->default)->emergency($message, $context); }
    public function alert($message, array $context = []): void       { $this->getLogger($this->default)->alert($message, $context); }
    public function critical($message, array $context = []): void    { $this->getLogger($this->default)->critical($message, $context); }
    public function error($message, array $context = []): void       { $this->getLogger($this->default)->error($message, $context); }
    public function warning($message, array $context = []): void     { $this->getLogger($this->default)->warning($message, $context); }
    public function notice($message, array $context = []): void      { $this->getLogger($this->default)->notice($message, $context); }
    public function info($message, array $context = []): void        { $this->getLogger($this->default)->info($message, $context); }
    public function debug($message, array $context = []): void       { $this->getLogger($this->default)->debug($message, $context); }
    public function log($level, $message, array $context = []): void { $this->getLogger($this->default)->log($level, $message, $context); }
}


# ---------------------------------------------------------------- #
# --------------[ СОЗДАЕМ И ПОДКЛЮЧАЕМ ИНТЕРФЕЙС ]---------------- #
# ---------------------------------------------------------------- #
$config = $this->config->get(__file__);
return new LogFactory($config);
