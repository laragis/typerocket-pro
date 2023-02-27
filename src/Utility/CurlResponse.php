<?php
namespace TypeRocket\Pro\Utility;

class CurlResponse
{
    protected $body;
    protected $headers;
    protected $code;
    protected $meta;
    protected $error;
    protected $with;

    /**
     * CurlResponse constructor.
     *
     * @param $body
     * @param $headers
     * @param $code
     * @param $meta
     * @param $error
     */
    public function __construct($body, $headers, $code, $meta, $error)
    {
        $this->body = $body;
        $this->headers = $headers;
        $this->code = $code;
        $this->meta = $meta;
        $this->error = $error;
    }

    /**
     * @return mixed
     */
    public function code()
    {
        return $this->code;
    }

    /**
     * @return bool
     */
    public function is200()
    {
        return $this->code < 300 && $this->code > 100;
    }

    /**
     * @return mixed
     */
    public function error()
    {
        return $this->error;
    }

    /**
     * @return mixed
     */
    public function headers()
    {
        return $this->headers;
    }

    /**
     * @return mixed
     */
    public function body()
    {
        return $this->body;
    }

    /**
     * @param string $key
     *
     * @return mixed|null
     */
    public function header($key)
    {
        $value = $this->headers[strtolower($key)] ?? [null];

        if(count($value) > 1) {
            return $value;
        }

        return $value[0];
    }

    /**
     * @return mixed
     */
    public function meta()
    {
        return $this->meta;
    }

    /**
     * @param null|mixed $data
     *
     * @return $this
     */
    public function with($data = null)
    {
        if(func_num_args() == 0) {
            return $this->with;
        }

        $this->with = $data;

        return $this;
    }

    /**
     * @return bool
     */
    public function hasWith()
    {
        return !empty($this->with);
    }
}