<?php
namespace TypeRocket\Pro\Utility\Mailers;

use TypeRocket\Pro\Utility\Log;
use TypeRocket\Pro\Utility\Mailers\MailDriver;

class LogMailDriver implements MailDriver
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
    public function send($to, $subject, $message, $headers = '', $attachments = []): bool
    {
        $bool = true;
        $sent = null;

        try {
            $message = json_encode([
                'to' => $to,
                'subject' => $subject,
                'message' => $message,
                'headers' => $headers,
                'attachments' => $attachments,
            ]);

            if(!$message) {
                $message = LogMailDriver::class . ': ' . json_last_error_msg();
            }

            $sent = Log::info($message);
        } catch (\Exception $e) {
            Log::info(LogMailDriver::class . ': ' . $message);
            $bool = false;
        }

        if(is_array($sent)) {
            foreach ($sent as $bool) {
                if(!$bool) {
                    return false;
                }
            }
        }

        return $bool;
    }
}