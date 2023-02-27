<?php
namespace TypeRocket\Pro\Utility;

use TypeRocket\Models\WPAttachment;

class ImageCache extends MasterCache
{
    /**
     * Inject Data
     *
     * The array of Ids passed into the cache must become the array keys
     * of the injected values.
     *
     * @param $ids
     *
     * @return array|\TypeRocket\Database\Results
     * @throws \Exception
     */
    protected static function inject(array $ids)
    {
        /** @var \TypeRocket\Database\Results $images */
        $images = (new WPAttachment())->with('meta')->findAll($ids)->get();
        $images = $images ? $images->indexWith('ID')->getArrayCopy() : [];
        static::$cache = array_replace(static::$cache, $images);
        return $images;
    }

    /**
     * Get Image Source
     *
     * @param $id
     * @param string $size
     *
     * @return mixed|string|null
     */
    public static function attachmentSrc($id, $size = 'full') {
        /** @var WPAttachment $img */
        $img = static::get($id) ?? null;

        if(!$img) {
            return null;
        }

        return $img->getUrlSize($size);
    }
}