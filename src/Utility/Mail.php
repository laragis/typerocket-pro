<?php
namespace TypeRocket\Pro\Utility;

use TypeRocket\Core\Config;
use TypeRocket\Core\Container;
use TypeRocket\Pro\Utility\Mailers\MailDriver;
use TypeRocket\Services\MailerService;
use TypeRocket\Template\View;

class Mail
{
    protected $driver;
    protected $as = 'html';
    protected $to = [];
    protected $subject = 'WordPress';
    protected $message;
    protected $headers = [];
    protected $attachments = [];

    /**
     * @return static
     */
    public static function new()
    {
        return new static;
    }

    /**
     * @param string|null|array $to
     *
     * @return array|null|$this
     */
    public function to($to = null)
    {
        if(func_num_args() === 0) {
            return $this->to;
        }

        if(is_string($to)) {
            $to = explode(',', $to);
        }

        $this->to = array_merge($this->to, $to);

        return $this;
    }

    /**
     * @param string|null|array $from
     *
     * @return $this
     */
    public function from($from = null)
    {
        if(func_num_args() === 0) {
            return $this->header('From');
        }

        if(is_string($from)) {
            $from = explode(',', $from);
        }

        return $this->header('From', implode(', ', $from));
    }

    /**
     * @param string|null $to
     *
     * @return $this
     */
    public function replyTo($to = null)
    {
        if(func_num_args() === 0) {
            return $this->header('Reply-To');
        }

        return $this->header('Reply-To', $to);
    }

    /**
     * @param null|string $subject
     *
     * @return $this|string
     */
    public function subject($subject = null)
    {
        if(func_num_args() === 0) {
            return $this->subject;
        }

        $this->subject = $subject;

        return $this;
    }

    /**
     * @param string $dots
     * @param array $data
     * @param string $ext
     *
     * @return $this
     */
    public function view($dots, array $data = [], $ext = '.php')
    {
        return $this->message(new View(...func_get_args()));
    }

    /**
     * @param string|View|null $message
     *
     * @return $this
     */
    public function message($message = null)
    {
        if(func_num_args() === 0) {
            return $this->message;
        }

        if($message instanceof View) {
            $message = $message->toString();
        }

        $this->message = $message;

        return $this;
    }

    /**
     * @param string $key
     * @param null|string $value
     *
     * @return $this
     */
    public function header($key, $value = null)
    {
        if(func_num_args() === 1) {
            return $this->headers[$key] ?? null;
        }

        $this->headers[$key] = $value;

        return $this;
    }

    /**
     * @param array $headers
     *
     * @return $this|array
     */
    public function headers($headers = [])
    {
        if(func_num_args() === 0) {
            return $this->headers;
        }

        foreach ($headers as $key => $value) {
            $this->header($key, $value);
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function asText() {
        $this->as = 'text';

        return $this;
    }

    /**
     * @param array $attachments array of full file paths
     */
    public function attachments(array $attachments)
    {
        $this->attachments = $attachments;

        return $this;
    }

    /**
     * @param MailDriver|string $driver
     *
     * @return $this
     */
    public function driver($driver)
    {
        if(!$driver instanceof MailDriver && is_string($driver)) {
            if($mailer = Config::get("mail.drivers.{$driver}")) {
                $driver = new $mailer['driver'];
            }
            elseif(class_exists($driver)) {
                $driver = new $driver;
            }
        }

        if(!$driver instanceof MailDriver) {
            throw new \Error(__('Class is not a MailDriver: ', 'typerocket-core') . get_class($driver));
        }

        $this->driver = $driver;

        return $this;
    }

    /**
     * @return bool
     */
    public function send() : bool
    {
        $compiled = $this->compile();
        $result = null;

        if($this->driver instanceof MailDriver) {
            /**
             * @var MailerService $mailer
             */
            $mailer = Container::resolveAlias(MailerService::ALIAS);
            $default_driver = $mailer->driver();
            $result = $mailer->driver($this->driver)->send(...$compiled);
            $mailer->driver($default_driver);
        }

        return $result ?? \wp_mail(...$compiled);
    }

    /**
     * @return array[]
     */
    protected function compile() {
        if($this->as == 'html' && empty($this->header['Content-Type']) ) {
            $this->header('Content-Type', 'text/html; charset=UTF-8');
        }

        $headers = [];
        foreach ($this->headers as $key => $value) {
            $headers[] = $key . ': ' . $value;
        }

        return [
            $this->to,
            $this->subject,
            $this->message,
            $headers ?: '',
            $this->attachments
        ];
    }
}