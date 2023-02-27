<?php
namespace TypeRocket\Pro\Utility\Loggers;

abstract class Logger
{
    /**
     * @return static
     */
    public static function new()
    {
        return new static;
    }

    /**
     * @param string $message
     *
     * @return bool
     */
    public function success($message)
    {
        return $this->log('success', $message);
    }

    /**
     * @param string $message
     *
     * @return bool
     */
    public function emergency($message)
    {
        return $this->log('emergency', $message);
    }

    /**
     * @param string $message
     *
     * @return bool
     */
    public function alert($message)
    {
        return $this->log('alert', $message);
    }

    /**
     * @param string $message
     *
     * @return bool
     */
    public function critical($message)
    {
        return $this->log('critical', $message);
    }

    /**
     * @param string $message
     *
     * @return bool
     */
    public function error($message)
    {
        return $this->log('error', $message);
    }

    /**
     * @param string $message
     *
     * @return bool
     */
    public function warning($message)
    {
        return $this->log('warning', $message);
    }

    /**
     * @param string $message
     *
     * @return bool
     */
    public function notice($message)
    {
        return $this->log('notice', $message);
    }

    /**
     * @param string $message
     *
     * @return bool
     */
    public function info($message)
    {
        return $this->log('info', $message);
    }

    /**
     * @param string $message
     *
     * @return bool
     */
    public function debug($message)
    {
        return $this->log('debug', $message);
    }

    /**
     * @param string $type
     * @param string $message
     * @param int|null $time unix timestamp
     * @param string|null $env
     *
     * @return string
     */
    protected function message($type, $message, $time = null, $env = null)
    {
        $env = $env ?? wp_get_environment_type();
        $time = $time ?? time();
        $time = date('Y-m-d H:i:s', $time);
        $type = strtoupper($type);
        return "[{$time}] {$env}.{$type} {$message}";
    }

    /**
     * @param string $type
     * @param string $message
     *
     * @return bool
     */
    abstract protected function log($type, $message) : bool;
}