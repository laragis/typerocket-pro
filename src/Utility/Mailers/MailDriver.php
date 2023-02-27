<?php
namespace TypeRocket\Pro\Utility\Mailers;

interface MailDriver
{
    /**
     * @param string|array $to
     * @param string $subject
     * @param string $message
     * @param string|array $headers
     * @param array $attachments
     *
     * @return bool
     */
    public function send($to, $subject, $message, $headers = '', $attachments = []) : bool;
}