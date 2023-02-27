<?php
/**
 * Add SEO Meta Fields
 *
 * @param \TypeRocket\Elements\BaseForm|\App\Elements\Form $form
 * @param null|\TypeRocket\Elements\Tabs $tabs
 *
 * @return false|string
 *
 * @throws \Exception
 */
function tr_seo_meta_fields(\TypeRocket\Elements\BaseForm $form, $tabs = null)
{
    ob_start();
    (new \TypeRocket\Pro\Extensions\Seo\PostTypeMeta)->fields($form, $tabs);
    return ob_get_clean();
}

/**
 * Create Table
 *
 * @param \TypeRocket\Models\Model|string|null $model
 * @param int $limit
 *
 * @return \TypeRocket\Pro\Elements\Table
 */
function tr_table($model = null, $limit = 25)
{
    return new \TypeRocket\Pro\Elements\Table($model, $limit);
}

/**
 * @return \TypeRocket\Pro\Http\RouteTemplate
 */
function tr_route_template()
{
    return new \TypeRocket\Pro\Http\RouteTemplate();
}

/**
 * Template Router
 *
 * @param callable|array|string|null $handler
 * @param array $args passed values to handler's method
 * @param array $construct passed values to handler's constructor
 * @param array $middleware
 *
 * @return \TypeRocket\Pro\Http\Template|null
 */
function tr_template_router($handler = null, $args = [], $construct = [], $middleware = [])
{
    if($handler) {
        \TypeRocket\Pro\Http\Template::respond($handler, $args, $construct, $middleware);
    } else {
        return new \TypeRocket\Pro\Http\Template;
    }

    return null;
}

/**
 * @param int|string $arg
 *
 * @return mixed
 */
function tr_image_cache(int $arg)
{
    return \TypeRocket\Pro\Utility\ImageCache::get($arg);
}

/**
 * @param array|\Traversable $data
 * @param null|string $dots
 *
 * @return mixed
 */
function tr_image_cache_index($data, $dots = null)
{
    return \TypeRocket\Pro\Utility\ImageCache::index($data, $dots);
}

/**
 * @param int|string $id
 * @param string $size
 *
 * @return mixed|string|null
 */
function tr_image_src($id, $size = 'full')
{
    return \TypeRocket\Pro\Utility\ImageCache::attachmentSrc($id, $size);
}