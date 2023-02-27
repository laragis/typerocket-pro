<?php
namespace TypeRocket\Pro\Services;

use TypeRocket\Core\Container;
use TypeRocket\Services\Service;
use TypeRocket\Pro\Http\RouteTemplateCollection;

class TemplateRouter extends Service
{
    public const ALIAS = 'template.router';

    /** @var RouteTemplateCollection */
    public $routes;

    /**
     * TemplateRouter constructor.
     *
     * @param RouteTemplateCollection $collection
     */
    public function __construct(RouteTemplateCollection $collection)
    {
        $this->routes = $collection;
        add_action('typerocket_after_routes', [$this, 'loadRoutes']);
    }

    /**
     * Init hooks after templates have been registered
     */
    public function loadRoutes()
    {
        $this->routes->detectTemplateRoute();
    }

    /**
     * @return static
     */
    public static function getFromContainer()
    {
        return Container::resolve(static::ALIAS);
    }
}