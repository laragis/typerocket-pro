<?php
namespace TypeRocket\Pro\Utility\Loggers;

use TypeRocket\Core\Config;
use TypeRocket\Utility\File;

class FileLogger extends Logger
{
    /**
     * @param string $type
     * @param string $message
     */
    protected function log($type, $message) : bool
    {
        $time = time();
        $config = Config::get('logging.drivers.file');
        $options = explode(':', $config['options'] ?? 'daily:joined', 2);
        $folder = typerocket_env('TYPEROCKET_LOG_FILE_FOLDER') ?? Config::get('paths.logs') ?? Config::get('paths.storage') . '/logs';
        $name = 'typerocket';

        if(empty($options[0]) || $options[0] === 'daily') {
            $name .= '-' . date('Y-m-d', $time );
        }

        // Options: joined, split
        if(!empty($options[1]) && $options[1] === 'split') {
            $name .= '-' . $type;
        }

        $file = rtrim($folder, DIRECTORY_SEPARATOR) . '/' . trim($name, DIRECTORY_SEPARATOR) . '.log';
        $message = $this->message($type, $message, $time);

        $file = apply_filters('typerocket_log_file', $file, $folder, $message, $options);

        return (bool) File::new($file)->append($message . PHP_EOL)->wrote();
    }
}