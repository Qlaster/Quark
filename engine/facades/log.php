<?php
namespace App\Facade;

use QyberTech\Logger\Logger;


# ---------------------------------------------------------------- #
#                      ИНТЕРФЕЙС                                   #
# ---------------------------------------------------------------- #
interface QLoggerInterface
{
    /** System is unusable. */
    public function emergency($message, array $context = []);
    /** Action must be taken immediately. */
    public function alert($message, array $context = []);
    /** Critical conditions. */
    public function critical($message, array $context = []);
    /** Runtime errors. */
    public function error($message, array $context = []);
    /** Exceptional occurrences that are not errors. */
    public function warning($message, array $context = []);
    /** Normal but significant events. */
    public function notice($message, array $context = []);
    /** Interesting events. */
    public function info($message, array $context = []);
    /** Detailed debug information. */
    public function debug($message, array $context = []);
    /** Logs with an arbitrary level. */
    public function log($level, $message, array $context = []);
    /** Returns logger for a named channel. */
    public function channel($name);
}

# ---------------------------------------------------------------- #
#                      РЕАЛИЗАЦИЯ                                  #
# ---------------------------------------------------------------- #
class LogFactory implements QLoggerInterface
{
    private $logger;

    public function __construct($config)
    {
        $dir     = $config['dir']     ?? 'app/log';
        $keep    = $config['rotate']  ?? 30;
        $default = $config['channel'] ?? 'app';

        $this->logger = new \QyberTech\Logger\Logger($dir, $default, $keep);
    }

    public function channel($name)
    {
        $clone         = clone $this;
        $clone->logger = $this->logger->channel($name);
        return $clone;
    }

    public function emergency($message, array $context = []) { $this->logger->emergency($message, $context); }
    public function alert($message, array $context = [])     { $this->logger->alert($message, $context); }
    public function critical($message, array $context = [])  { $this->logger->critical($message, $context); }
    public function error($message, array $context = [])     { $this->logger->error($message, $context); }
    public function warning($message, array $context = [])   { $this->logger->warning($message, $context); }
    public function notice($message, array $context = [])    { $this->logger->notice($message, $context); }
    public function info($message, array $context = [])      { $this->logger->info($message, $context); }
    public function debug($message, array $context = [])     { $this->logger->debug($message, $context); }
    public function log($level, $message, array $context = []) { $this->logger->log($level, $message, $context); }
}

# ---------------------------------------------------------------- #
#                      ПОДКЛЮЧЕНИЕ                                 #
# ---------------------------------------------------------------- #
$config = $this->config->get(__file__);
return new LogFactory($config);
