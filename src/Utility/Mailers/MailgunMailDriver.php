<?php
namespace TypeRocket\Pro\Utility\Mailers;

use TypeRocket\Core\Config;
use TypeRocket\Pro\Utility\Log;
use TypeRocket\Utility\Data;

class MailgunMailDriver implements MailDriver
{
    protected $options;

    /**
     * @param string|array $to
     * @param string $subject
     * @param string $message
     * @param string|array $headers
     * @param array $attachments
     *
     * @return bool
     * @throws \Exception
     */
    public function send($to, $subject, $message, $headers = '', $attachments = []) : bool
    {
        // Compact the input, apply the filters, and extract them back out
        extract(apply_filters('wp_mail', compact('to', 'subject', 'message', 'headers', 'attachments')));

        $mailgun = $this->getOptions();
        $region = $mailgun['region'];
        $apiKey = $mailgun['api_key'];
        $domain = $mailgun['domain'];

        if (empty($apiKey) || empty($domain)) {
            Log::critical('[Mailgun] No API Key or domain set.');
            return false;
        }

        if (!$region) {
            Log::warning('[Mailgun] No region configuration was found! Defaulting to US region.');
            $region = 'us';
        }

        if (!is_array($attachments)) {
            $attachments = explode("\n", str_replace("\r\n", "\n", $attachments));
        }

        // Headers
        if (empty($headers)) {
            $headers = [];
        }
        else {
            if (!is_array($headers)) {
                // Explode the headers out, so this function can take both
                // string headers and an array of headers.
                $temp_headers = explode("\n", str_replace("\r\n", "\n", $headers));
            } else {
                $temp_headers = $headers;
            }
            $headers = [];
            $cc = [];
            $bcc = [];

            // If it's actually got contents
            if (!empty($temp_headers)) {
                // Iterate through the raw headers
                foreach ((array) $temp_headers as $header) {
                    if (strpos($header, ':') === false) {
                        continue;
                    }

                    // Explode them out
                    [$name, $content] = array_map('trim', explode(':', trim($header), 2));

                    switch (strtolower($name)) {
                        // Mainly for legacy -- process a From: header if it's there
                        case 'from':
                            if (strpos($content, '<') !== false) {
                                // So... making my life hard again?
                                $from_name = substr($content, 0, strpos($content, '<') - 1);
                                $from_name = str_replace('"', '', $from_name);
                                $from_name = trim($from_name);

                                $from_email = substr($content, strpos($content, '<') + 1);
                                $from_email = str_replace('>', '', $from_email);
                                $from_email = trim($from_email);
                            } else {
                                $from_email = trim($content);
                            }
                            break;
                        case 'content-type':
                            if (strpos($content, ';') !== false) {
                                [$type, $charset] = explode(';', $content);
                                $content_type = trim($type);
                                if (false !== stripos($charset, 'charset=')) {
                                    $charset = trim(str_replace(['charset=', '"'], '', $charset));
                                } elseif (false !== stripos($charset, 'boundary=')) {
                                    $charset = '';
                                }
                            } else {
                                $content_type = trim($content);
                            }
                            break;
                        case 'cc':
                            $cc = array_merge((array) $cc, explode(',', $content));
                            break;
                        case 'bcc':
                            $bcc = array_merge((array) $bcc, explode(',', $content));
                            break;
                        default:
                            // Add it to our grand headers array
                            $headers[trim($name)] = trim($content);
                            break;
                    }
                }
            }
        }

        $from_name = $this->detectFromName($from_name ?? null);
        $from_email = $this->detectFromAddress($from_email ?? null);

        $body = [
            'from'    => "{$from_name} <{$from_email}>",
            'to'      => $to,
            'subject' => $subject,
        ];

        $body['o:tag'] = !empty($mailgun['tags']) ? $mailgun['tags'] : [];
        $body['o:tracking-clicks'] = !empty($mailgun['track_clicks']) ? 'yes' : 'no';
        $body['o:tracking-opens'] = !empty($mailgun['track_opens']) ? 'yes' : 'no';
        empty($mailgun['testmode']) ?: $body['o:testmode'] = 'yes';

        if (!empty($cc) && is_array($cc)) {
            $body['cc'] = implode(', ', $cc);
        }

        if (!empty($bcc) && is_array($bcc)) {
            $body['bcc'] = implode(', ', $bcc);
        }

        // If we are not given a Content-Type in the supplied headers,
        // write the message body to a file and try to determine the mimetype
        // using get_mime_content_type.
        if (!isset($content_type)) {
            $tmppath = tempnam(sys_get_temp_dir(), 'mg');
            $tmp = fopen($tmppath, 'w+');

            fwrite($tmp, $message);
            fclose($tmp);

            $content_type = $this->getMimeContentType($tmppath, 'text/plain');

            unlink($tmppath);
        }

        // Allow external content type filter to function normally
        if (has_filter('wp_mail_content_type')) {
            $content_type = apply_filters('wp_mail_content_type', $content_type);
        }

        switch ($content_type) {
            case 'text/plain':
                $body['text'] = $message;
                break;
            case 'text/html':
                $body['html'] = $message;
                break;
            default :
                Log::critical('[Mailgun] Got unknown Content-Type: ' . $content_type);
                $body['text'] = $message;
                $body['html'] = $message;
                break;
        }

        // Set the content-type and charset
        $charset = apply_filters('wp_mail_charset', $charset ?? get_bloginfo('charset'));

        if (isset($headers['Content-Type'])) {
            if (!strstr($headers['Content-Type'], 'charset')) {
                $headers['Content-Type'] = rtrim($headers['Content-Type'], '; ')."; charset={$charset}";
            }
        }

        // Set custom headers
        if (!empty($headers)) {
            foreach ((array) $headers as $name => $content) {
                $body["h:{$name}"] = $content;
            }
        }

        /*
         * Deconstruct post array and create POST payload.
         * This entire routine is because wp_remote_post does
         * not support files directly.
         */
        $payload = '';

        // First, generate a boundary for the multipart message.
        $boundary = 'boundary-' . bin2hex(random_bytes(11));

        // Allow other plugins to apply body changes before creating the payload.
        $body = apply_filters('typerocket_mail_driver_mailgun_message_body', $body);
        if ( ($body_payload = $this->buildPayloadFromBody($body, $boundary)) != null ) {
            $payload .= $body_payload;
        }

        // Allow other plugins to apply attachment changes before writing to the payload.
        $attachments = apply_filters('typerocket_mail_driver_mailgun_attachments', $attachments);
        if ( ($attachment_payload = $this->buildAttachmentsPayload($attachments, $boundary)) != null ) {
            $payload .= $attachment_payload;
        }

        $payload .= '--'.$boundary.'--';

        $data = [
            'body'    => $payload,
            'headers' => [
                'Authorization' => 'Basic '.base64_encode("api:{$apiKey}"),
                'Content-Type'  => 'multipart/form-data; boundary='.$boundary,
            ],
        ];

        $endpoint = $this->apiGetEndpoint($region);
        $url = $endpoint."{$domain}/messages";

        // TODO: Mailgun only supports 1000 recipients per request, since we are
        // overriding this function, let's add looping here to handle that
        $response = wp_remote_post($url, $data);
        if (is_wp_error($response) || (is_array($response) && $response['response']['code'] > 299) ) {
            // Store WP error in last error.
            if(is_wp_error($response)) {
                $error_message = $response->get_error_message();
            } else {
                $error_message = Data::isJson($response['body']) ? json_decode($response['body'], true)['message'] : $response['body'];
            }
            $this->apiLastError($error_message);

            return false;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = json_decode(wp_remote_retrieve_body($response));

        // Response is null?
        if(!isset($response_body)) {
            $this->apiLastError('Unknown error. Possible unverified email address used.');

            return false;
        }

        // Mailgun API should *always* return a `message` field, even when
        // $response_code != 200, so a lack of `message` indicates something
        // is broken.
        if ((int) $response_code != 200 && !isset($response_body->message)) {
            // Store response code and HTTP response message in last error.
            $response_message = wp_remote_retrieve_response_message($response);
            $errmsg = "$response_code - $response_message";
            $this->apiLastError($errmsg);

            return false;
        }

        // Not sure there is any additional checking that needs to be done here, but why not?
        if ($response_body->message != 'Queued. Thank you.') {
            $this->apiLastError($response_body->message);

            return false;
        }



        return true;
    }

    /**
     * @param array $body
     * @param string $boundary
     *
     * @return string
     */
    protected function buildPayloadFromBody($body, $boundary)
    {
        $payload = '';

        foreach ($body as $key => $value) {
            if (is_array($value)) {
                $parent_key = $key;
                foreach ($value as $key => $value) {
                    $payload .= '--'.$boundary;
                    $payload .= "\r\n";
                    $payload .= 'Content-Disposition: form-data; name="'.$parent_key."\"\r\n\r\n";
                    $payload .= $value;
                    $payload .= "\r\n";
                }
            } else {
                $payload .= '--'.$boundary;
                $payload .= "\r\n";
                $payload .= 'Content-Disposition: form-data; name="'.$key.'"'."\r\n\r\n";
                $payload .= $value;
                $payload .= "\r\n";
            }
        }

        return $payload;
    }

    /**
     * @param array $attachments
     * @param string $boundary
     *
     * @return string|null
     */
    protected function buildAttachmentsPayload($attachments, $boundary)
    {
        $payload = '';

        // If we have attachments, add them to the payload.
        if (!empty($attachments)) {
            $i = 0;
            foreach ($attachments as $attachment) {
                if (!empty($attachment)) {
                    $payload .= '--'.$boundary;
                    $payload .= "\r\n";
                    $payload .= 'Content-Disposition: form-data; name="attachment['.$i.']"; filename="'.basename($attachment).'"'."\r\n\r\n";
                    $payload .= file_get_contents($attachment);
                    $payload .= "\r\n";
                    $i++;
                }
            }
        } else {
            return null;
        }

        return $payload;
    }

    /**
     * A compound getter/setter for the last error that was
     * encountered during a Mailgun API call.
     *
     * @param string|null $error	OPTIONAL
     *
     * @return string Last error that occurred.
     */
    protected function apiLastError($error = null)
    {
        static $last_error;

        Log::critical('[Mailgun] ' . $error);

        if (null === $error) {
            return $last_error;
        } else {
            $tmp = $last_error;
            $last_error = $error;

            return $tmp;
        }
    }

    /**
     * Tries several methods to get the MIME Content-Type of a file.
     *
     * @param	string	$filepath
     * @param	string	$default_type	If all methods fail, fallback to $default_type
     *
     * @return	string	Content-Type
     */
    function getMimeContentType($filepath, $default_type = 'text/plain')
    {
        if (function_exists('mime_content_type')) {
            return mime_content_type($filepath);
        } elseif (function_exists('finfo_file')) {
            $fi = finfo_open(FILEINFO_MIME_TYPE);
            $ret = finfo_file($fi, $filepath);
            finfo_close($fi);

            return $ret;
        } else {
            return $default_type;
        }
    }

    /**
     * Find the sending "From Name" with a similar process used in `wp_mail`.
     * This operates as a filter for the from name. If the override is set,
     * a given name will clobbered except in ONE case.
     * If the override is not enabled this is the from name resolution order:
     *  1. From name given by headers - {@param $from_name_header}
     *  2. From name set in Mailgun settings
     *  3. From `MAILGUN_FROM_NAME` constant
     *  4. From name constructed as `<your_site_title>` or "WordPress"
     *
     * If the `wp_mail_from` filter is available, it is applied to the resulting
     * `$from_addr` before being returned. The filtered result is null-tested
     * before being returned.
     *
     * @return	string
     */
    protected function detectFromName($from_name_header = null)
    {
        // Get options to avoid strict mode problems
        $mailgun = $this->getOptions();
        $_override_from = $mailgun['from_override'];
        $_from_name = $mailgun['from_name'];

        $from_name = null;

        if ($_override_from && !is_null($_from_name)) {
            $from_name = $_from_name;
        } elseif (!is_null($from_name_header)) {
            $from_name = $from_name_header;
        } else {
            if (is_null($_from_name) || empty($_from_name)) {
                if (function_exists('get_current_site')) {
                    $from_name = get_current_site()->site_name;
                } else {
                    $from_name = 'WordPress';
                }
            } else {
                $from_name = $_from_name;
            }
        }

        if (has_filter('wp_mail_from_name')) {
            $filter_from_name = apply_filters('wp_mail_from_name', $from_name);
            $from_name = !empty($filter_from_name) ? $filter_from_name : $from_name;
        }

        return $from_name;
    }

    /**
     * Find the sending "From Address" with a similar process used in `wp_mail`.
     * This operates as a filter for the from address. If the override is set,
     * a given address will except in ONE case.
     * If the override is not enabled this is the from address resolution order:
     *  1. From address given by headers - {@param $from_addr_header}
     *  2. From address set in Mailgun settings
     *  3. From `MAILGUN_FROM_ADDRESS` constant
     *  4. From address constructed as `wordpress@<your_site_domain>`
     *
     * If the `wp_mail_from` filter is available, it is applied to the resulting
     * `$from_addr` before being returned. The filtered result is null-tested
     * before being returned.
     *
     * If we don't have `From` input headers, use wordpress@$sitedomain
     * Some hosts will block outgoing mail from this address if it doesn't
     * exist but there's no easy alternative. Defaulting to admin_email
     * might appear to be another option but some hosts may refuse to
     * relay mail from an unknown domain.
     *
     * @link	http://trac.wordpress.org/ticket/5007.
     *
     * @return	string
     */
    protected function detectFromAddress($from_addr_header = null)
    {
        // Get options to avoid strict mode problems
        $mailgun = $this->getOptions();
        $_override_from = $mailgun['from_override'];
        $_from_addr = $mailgun['from_address'];

        $from_addr = null;

        if ($_override_from && !is_null($_from_addr)) {
            $from_addr = $_from_addr;
        } elseif (!is_null($from_addr_header)) {
            $from_addr = $from_addr_header;
        } else {
            if (is_null($_from_addr) || empty($_from_addr)) {
                if (function_exists('get_current_site')) {
                    $sitedomain = get_current_site()->domain;
                } else {
                    $sitedomain = strtolower($_SERVER['SERVER_NAME']);
                    if (substr($sitedomain, 0, 4) === 'www.') {
                        $sitedomain = substr($sitedomain, 4);
                    }
                }

                $from_addr = 'wordpress@'.$sitedomain;
            } else {
                $from_addr = $_from_addr;
            }
        }

        if (has_filter('wp_mail_from')) {
            $filter_from_addr = apply_filters('wp_mail_from', $from_addr);
            $from_addr = !empty($filter_from_addr) ? $filter_from_addr : $from_addr;
        }

        return $from_addr;
    }

    /**
     * Set the API endpoint based on the region selected.
     * Value can be "0" if not selected, "us" or "eu"
     *
     * @param string $getRegion	Region value set in config
     *
     * @return bool|string
     *
     * @since	1.5.12
     */
    protected function apiGetEndpoint($getRegion)
    {
        if($getRegion == 'eu') {
            return 'https://api.eu.mailgun.net/v3/';
        }

        return 'https://api.mailgun.net/v3/';
    }

    /**
     * @return false|mixed|void
     */
    public function getOptions()
    {
        if($this->options) {
            return $this->options;
        }

        $this->options = apply_filters('typerocket_mail_driver_mailgun_options', Config::get('mail.drivers.mailgun', [])) ;

        return $this->options;
    }
}