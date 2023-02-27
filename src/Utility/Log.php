<?php
namespace TypeRocket\Pro\Utility;

use TypeRocket\Core\Config;
use TypeRocket\Pro\Utility\Loggers\Logger;

/**
 * Class Log - Generate RFC 5424 Log Levels
 *
 * @link https://www.rfc-editor.org/rfc/rfc5424
 *
 * @method static bool success(string $message)
 * @method static bool emergency(string $message)
 * @method static bool alert(string $message)
 * @method static bool critical(string $message)
 * @method static bool error(string $message)
 * @method static bool warning(string $message)
 * @method static bool notice(string $message)
 * @method static bool info(string $message)
 * @method static bool debug(string $message)
 *
 * @package TypeRocket\Utility
 */
class Log
{
    /**
     * @param array $stack
     * @param string $type
     * @param string $message
     *
     * @return array
     */
    public static function stack(array $stack, $type, $message) : array
    {
        $response = [];
        $stack = array_filter($stack);

        foreach ($stack as $channel) {
            $response[$channel] = static::driver($channel)->{$type}($message);
        }

        return $response;
    }

    /**
     * @param $name
     * @param $arguments
     *
     * @return bool|array
     */
    public static function __callStatic($name, $arguments)
    {
        $channel = Config::get('logging.default');

        if($channel === 'stack') {
            $stack = Config::get("logging.drivers.{$channel}");
            return static::stack($stack, $name, ...$arguments);
        } else {
            $logger = static::driver($channel);
        }

        return $logger->{$name}(...$arguments);
    }

    /**
     * @param string|Logger $driver
     *
     * @return Logger
     */
    public static function driver($driver) : Logger
    {
        if(!$driver instanceof Logger && is_string($driver)) {
            if($logger = Config::get("logging.drivers.{$driver}")) {
                $driver = new $logger['driver'];
            }
            elseif(class_exists($driver)) {
                $driver = new $driver;
            }
        }

        if(!$driver instanceof Logger) {
            throw new \Error(__('Class is not a Logger: ', 'typerocket-core') . get_class($driver));
        }

        return $driver;
    }
}