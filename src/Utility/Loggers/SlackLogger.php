<?php
namespace TypeRocket\Pro\Utility\Loggers;

use TypeRocket\Core\Config;
use TypeRocket\Pro\Utility\Http;
use TypeRocket\Pro\Utility\Loggers\Logger;

class SlackLogger extends Logger
{
    /**
     * @param string $type
     * @param string $message
     *
     * @link https://api.slack.com/reference/surfaces/formatting
     *
     * @return bool
     */
    protected function log($type, $message): bool
    {
        $channel = Config::get('logging.drivers.slack');
        $message = $this->message($type, $message);

        $request = Http::post($channel['url'], [
            "text" => $channel['emoji'] . ' ' . esc_html($message)
        ], true);

        return $request->exec()->code() == 200;
    }
}