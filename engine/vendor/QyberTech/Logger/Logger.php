<?php
namespace QyberTech\Logger;

class Logger
{
    const DEBUG     = 'DEBUG';
    const INFO      = 'INFO';
    const NOTICE    = 'NOTICE';
    const WARNING   = 'WARNING';
    const ERROR     = 'ERROR';
    const CRITICAL  = 'CRITICAL';
    const ALERT     = 'ALERT';
    const EMERGENCY = 'EMERGENCY';

    private static $levelMap = [
        'debug'     => 100,
        'info'      => 200,
        'notice'    => 250,
        'warning'   => 300,
        'error'     => 400,
        'critical'  => 500,
        'alert'     => 550,
        'emergency' => 600,
    ];

    public $config = [];

    public function __construct($dir, $channel = 'app', $keep = 30, $level = 'debug')
    {
        $this->config['dir']     = rtrim($dir, '/');
        $this->config['channel'] = $this->sanitize($channel);
        $this->config['keep']    = (int) $keep;
        $this->config['level']   = strtolower($level);
    }

    public function channel($name)
    {
        $clone = clone $this;
        $clone->config['channel'] = $clone->sanitize($name);
        return $clone;
    }

    public function emergency($message, array $context = []) { $this->write(self::EMERGENCY, $message, $context); }
    public function alert($message, array $context = [])     { $this->write(self::ALERT,     $message, $context); }
    public function critical($message, array $context = [])  { $this->write(self::CRITICAL,  $message, $context); }
    public function error($message, array $context = [])     { $this->write(self::ERROR,      $message, $context); }
    public function warning($message, array $context = [])   { $this->write(self::WARNING,    $message, $context); }
    public function notice($message, array $context = [])    { $this->write(self::NOTICE,     $message, $context); }
    public function info($message, array $context = [])      { $this->write(self::INFO,       $message, $context); }
    public function debug($message, array $context = [])     { $this->write(self::DEBUG,      $message, $context); }

    public function log($level, $message, array $context = [])
    {
        $this->write(strtoupper($level), $message, $context);
    }

    private function sanitize($name)
    {
        $name = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', (string) $name);
        return $name !== '' ? $name : 'app';
    }

    private function write($level, $message, array $context)
    {
        $minLevel = self::$levelMap[$this->config['level']] ?? 100;
        $msgLevel = self::$levelMap[strtolower($level)]     ?? 0;
        if ($msgLevel < $minLevel) return;

        $date = date('Y-m-d');
        $time = date('Y-m-d H:i:s');
        $ctx  = $context ? ' ' . json_encode($context) : '';
        $line = "[{$time}] [{$this->config['channel']}] {$level}: {$message}{$ctx}" . PHP_EOL;

        $dir = "{$this->config['dir']}/{$this->config['channel']}";
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        file_put_contents("{$dir}/{$date}.log", $line, FILE_APPEND | LOCK_EX);

        $this->rotate($dir);
    }

    private function rotate($dir)
    {
        if ($this->config['keep'] <= 0) return;

        $files = glob("{$dir}/*.log");
        if (!$files || count($files) <= $this->config['keep']) return;

        rsort($files);

        foreach (array_slice($files, $this->config['keep']) as $old)
            if (is_writable($old)) @unlink($old);

    }
}
