<?php
namespace TypeRocket\Pro\Utility\Loggers;

use TypeRocket\Core\Config;
use TypeRocket\Pro\Utility\Mail;
use TypeRocket\Pro\Utility\Loggers\Logger;

class MailLogger extends Logger
{
    /**
     * @param string $type
     * @param string $message
     *
     * @return bool
     */
    protected function log($type, $message): bool
    {
        $channel = Config::get('logging.drivers.mail');
        $mailer = $channel['mailer'];
        $mail = Mail::new();

        if($mailer !== 'default') {
            $driver = Config::get("mail.drivers.{$mailer}")['driver'];
            $mail->driver(new $driver);
        }

        $mail
            ->to( filter_var($channel['to'], FILTER_VALIDATE_EMAIL) ? $channel['to'] : \get_option('admin_email') )
            ->subject($channel['subject'])
            ->message($this->message($type, $message));

        return $mail->send();
    }
}