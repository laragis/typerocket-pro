<?php
namespace TypeRocket\Pro\Utility\Drives;

use TypeRocket\Pro\Http\Download;

abstract class Drive
{
    /**
     * @return static
     */
    public static function new()
    {
        return new static;
    }

    /**
     * @param string $file
     * @param string $content
     *
     * @return bool
     */
    abstract public function create($file, $content) : bool;

    /**
     * @param string $file
     * @param string $content
     *
     * @return bool
     */
    abstract public function replace($file, $content) : bool;

    /**
     * @param string $file
     * @param string $content
     *
     * @return bool
     */
    abstract public function append($file, $content) : bool;

    /**
     * @param string $file
     * @param string $content
     *
     * @return bool
     */
    abstract public function delete($file) : bool;

    /**
     * @param string $file
     *
     * @return mixed
     */
    abstract public function get($file);

    /**
     * @param string $file
     *
     * @return bool
     */
    abstract public function exists($file) : bool;

    /**
     * @param string|null $file
     *
     * @return mixed
     */
    abstract public function path($file = null);

    /**
     * @param string $file
     *
     * @return mixed
     */
    abstract public function size($file);

    /**
     * @param string $file
     *
     * @return mixed
     */
    abstract public function lastModified($file);

    /**
     * @param string $file
     * @param string|null $name
     * @param array|null $headers
     * @param string|null $type
     *
     * @return Download
     */
    abstract public function download($file, $name = null, ?array $headers = null, $type = null): Download;
}