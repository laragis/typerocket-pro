<?php
namespace TypeRocket\Pro\Utility;

abstract class MasterCache
{
    protected static $cache = [];

    /**
     * Get From Cache By ID
     *
     * @param int $id
     *
     * @return mixed
     */
    public static function get($id) {
        return static::$cache[(int)$id] ?? null;
    }

    /**
     * Prepare To Inject
     *
     * @param array $array an array of integers only
     *
     * @return mixed
     */
    public static function prepare(array $array) {
        if(is_array($array)) {
            $existing = array_keys(static::$cache);
            $clean = array_unique(array_filter(array_map('intval', $array)));
            $ids = array_diff($clean, $existing);
            if(!empty($ids)) {
                return static::inject($ids);
            }
        }

        return null;
    }

    /**
     * Inject Data
     *
     * The array of Ids passed into the cache must become the array keys
     * of the injected values.
     *
     * @param array $ids an array of integers only
     *
     * @return mixed
     */
    protected static function inject(array $ids)
    {
        $caches = array_flip($ids);
        static::$cache = array_replace(static::$cache, $caches);

        return $caches;
    }

    /**
     * Filter Data For IDs
     *
     * Filter data and return a set of IDs that can be used by the cache.
     *
     * @param array|\Traversable $data
     * @param null|string $dots
     *
     * @return array
     */
    public static function filter($data, $dots = null) {
        $index = $clone = explode('.', $dots);
        $fields = [];

        foreach ($data as $di => $d) {
            foreach ($index as $i => $dot) {
                unset($clone[$i]);
                if($dot === '*') {
                    $f = static::filter($d, implode('.',$clone));
                    $fields = array_merge($fields, $f);
                    break;
                }

                if($dot)
                    $d = is_object($d) ?  $d->{$dot} : $d[$dot];

            }

            if(empty($f)) {
                $fields[] = $d;
            }
        }

        return $fields;
    }

    /**
     * Index Data
     *
     * Filter and then prepare and then inject the IDs that were filtered
     * into the cache.
     *
     * @param array|\Traversable $data
     * @param null|string $dots
     *
     * @return mixed
     */
    public static function index($data, $dots = null) {
        return static::prepare(static::filter($data, $dots));
    }
}