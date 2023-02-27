<?php
namespace TypeRocket\Pro\Utility;

class HttpAsync
{
    protected $multi;

    /** @var Http[]|null */
    protected $https;

    /**
     * @return static
     */
    public static function new()
    {
        return new static;
    }

    /**
     * HttpAsync constructor.
     */
    public function __construct()
    {
        $this->multi = curl_multi_init();
    }

    /**
     * @param Http[] ...$curls
     */
    public function apply(...$curls)
    {
        $options = func_get_args();
        /** @var Http[] $options */
        foreach ($options as $curl) {
            $this->https[] = $curl;
            curl_multi_add_handle($this->multi,$curl->curl());
        }
    }

    /**
     * @param null|mixed $multi
     *
     * @return $this|false|resource|null
     */
    public function &multi($multi = null)
    {
        if(func_num_args() == 0) {
            return $this->multi;
        }

        if(is_null($multi)) {
            curl_multi_close($this->multi);
        }

        $this->multi = $multi;

        return $this;
    }

    /**
     * @return array
     */
    public function exec()
    {
        do {
            curl_multi_exec($this->multi, $running);
            curl_multi_select($this->multi);
        } while ($running > CURLM_OK);

        $response = [];

        foreach ($this->https as $http) {
            $return = curl_multi_getcontent($this->multi, $http->curl());
            $response[] = Http::response($return, $http->curl(), $http->getResponseHeaders());
            curl_multi_remove_handle($this->multi, $http->curl());
        }

        $this->multi(null);

        return $response;
    }
}