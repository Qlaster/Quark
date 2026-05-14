<?php

namespace App\Facade;

use QyberTech\Logger\Logger;


# ---------------------------------------------------------------- #
#                 ЭКСПОРТИРУЕМ ИНТЕРФЕЙС                           #
# ---------------------------------------------------------------- #
interface QLoggerInterface
{
    /** System is unusable. */
    public function emergency($message, array $context = []);

    /** Action must be taken immediately. */
    public function alert($message, array $context = []);

    /** Critical conditions. */
    public function critical($message, array $context = []);

    /** Runtime errors that do not require immediate action. */
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

    /** Returns instance for the given channel. */
    public function channel($name);
}


# ---------------------------------------------------------------- #
#                 РЕАЛИЗАЦИЯ ФАСАДА                                #
# ---------------------------------------------------------------- #
class LogFactory implements QLoggerInterface
{
    public  $config  = [];
    private $loggers = [];

    public function __construct($config)
    {
        $this->config = [
            'dir'     => rtrim($config['dir']    ?? 'app/log', '/'),
            'rotate'  => (int)($config['rotate'] ?? 30),
            'level'   => $config['level']        ?? 'debug',
            'default' => $config['default']      ?? 'app',
            'channel' => $config['channel']      ?? [],
        ];
    }

    public function channel($name)
    {
        $clone = clone $this;
        $clone->config['default'] = $name;
        return $clone;
    }

    private function getLogger($name)
    {
        $name = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', (string) $name);
        if ($name === '') $name = 'app';

        if (!isset($this->loggers[$name])) {
            $cfg   = $this->config['channel'][$name] ?? [];
            $level = $cfg['level']                   ?? $this->config['level'];
            $keep  = isset($cfg['rotate']) ? (int) $cfg['rotate'] : $this->config['rotate'];

            $this->loggers[$name] = new Logger($this->config['dir'], $name, $keep, $level);
        }

        return $this->loggers[$name];
    }

    public function emergency($message, array $context = []) { $this->getLogger($this->config['default'])->emergency($message, $context); }
    public function alert($message, array $context = [])     { $this->getLogger($this->config['default'])->alert($message, $context); }
    public function critical($message, array $context = [])  { $this->getLogger($this->config['default'])->critical($message, $context); }
    public function error($message, array $context = [])     { $this->getLogger($this->config['default'])->error($message, $context); }
    public function warning($message, array $context = [])   { $this->getLogger($this->config['default'])->warning($message, $context); }
    public function notice($message, array $context = [])    { $this->getLogger($this->config['default'])->notice($message, $context); }
    public function info($message, array $context = [])      { $this->getLogger($this->config['default'])->info($message, $context); }
    public function debug($message, array $context = [])     { $this->getLogger($this->config['default'])->debug($message, $context); }
    public function log($level, $message, array $context = []) { $this->getLogger($this->config['default'])->log($level, $message, $context); }
}


# ---------------------------------------------------------------- #
#                      СОЗДАЁМ ЭКЗЕМПЛЯР                           #
# ---------------------------------------------------------------- #
$config = $this->config->get(__file__);
return new LogFactory($config);
