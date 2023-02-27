<?php
namespace TypeRocket\Pro\Utility\Mailers;

use PHPMailer\PHPMailer\PHPMailer;

class WordPressMailDriver implements MailDriver
{
    /**
     * @param string|array $to
     * @param string $subject
     * @param string $message
     * @param string|array $headers
     * @param array $attachments
     *
     * Watch this thread for updates to wp_mail
     *
     * @link https://github.com/WordPress/WordPress/commits/master/wp-includes/pluggable.php
     *
     * @return bool
     */
    public function send( $to, $subject, $message, $headers = '', $attachments = array() ) : bool
    {
        try {
            remove_filter('pre_wp_mail', 'typerocket_mail_service_override_wp_mail', 0);
            $result = \wp_mail(...func_get_args());
        } catch (\Throwable $e) {
            add_filter('pre_wp_mail', 'typerocket_mail_service_override_wp_mail', 0, 2);

            throw $e;
        }

        add_filter('pre_wp_mail', 'typerocket_mail_service_override_wp_mail', 0, 2);

        return $result;
    }
}