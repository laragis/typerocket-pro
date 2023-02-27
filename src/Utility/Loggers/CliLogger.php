<?php
namespace TypeRocket\Pro\Utility\Loggers;

class CliLogger extends Logger
{
    /**
     * @param string $type
     * @param string $message
     */
    protected function log($type, $message) : bool
    {
        $time = time();
        $message = apply_filters('typerocket_log_cli', $this->message($type, $message, $time), $type);

        if(!$message) {
            return true;
        }

        $color_default = "\e[39m";

        if(php_sapi_name() === 'cli') {
            switch ($type) {
                case 'emergency' :
                case 'critical' :
                    echo "\e[41m";
                    break;
                case 'alert' :
                    echo "\e[43m";
                    break;
                case 'error' :
                    echo "\e[31m";
                    break;
                case 'success' :
                    echo "\e[32m";
                    break;
                case 'warning' :
                    echo "\e[33m";
                    break;
                default :
                    echo $color_default;
            }

            echo $message . $color_default . PHP_EOL;
        }

        return true;
    }
}