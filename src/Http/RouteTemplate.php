<?php
namespace TypeRocket\Pro\Http;

use TypeRocket\Http\Request;
use TypeRocket\Http\Response;
use TypeRocket\Pro\Services\TemplateRouter;

class RouteTemplate
{
    protected $type;
    protected $slug;
    protected $middleware;
    /** @var array */
    protected $handler;
    protected $methods;

    public function __construct()
    {
        TemplateRouter::getFromContainer()->routes->addRoute($this);
    }

    /**
     * @return static
     */
    public static function new()
    {
        return new static;
    }

    /**
     * Add Get Route
     *
     * @return $this
     */
    public function get($handler)
    {
        $this->methods['GET'] = $handler;
        return $this;
    }

    /**
     * Add Post Route
     *
     * @param $handler
     *
     * @return $this
     */
    public function post($handler)
    {
        $this->methods['POST'] = $handler;
        return $this;
    }

    /**
     * Add Put Route
     *
     * @param $handler
     *
     * @return $this
     */
    public function put($handler)
    {
        $this->methods['PUT'] = $handler;
        return $this;
    }

    /**
     * Add Delete Route
     *
     * @param $handler
     *
     * @return $this
     */
    public function delete($handler)
    {
        $this->methods['DELETE'] = $handler;
        return $this;
    }

    /**
     * Add Patch Route
     *
     * @param $handler
     *
     * @return $this
     */
    public function patch($handler)
    {
        $this->methods['PATCH'] = $handler;
        return $this;
    }

    /**
     * Add Options Route
     *
     * @param $handler
     *
     * @return $this
     */
    public function options($handler)
    {
        $this->methods['OPTIONS'] = $handler;
        return $this;
    }

    /**
     * Type
     *
     * Types include: index, 404, archive, author, category, tag, taxonomy, date,
     * embed, home, frontpage, privacypolicy, page, paged, search, single,
     * singular, attachment
     *
     * @param string|null $type
     *
     * @return RouteTemplate|string
     */
    public function type(?string $type = null)
    {
        if(func_num_args() === 0) {
            return $this->type;
        }

        $this->type = $type;

        return $this;
    }

    /**
     * Slug
     *
     * When setting the slug it should be the exact name of the template
     * used by WordPress including the .php extension.
     *
     * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
     *
     * @param string|null $slug
     *
     * @return RouteTemplate|string
     */
    public function slug(?string $slug = null)
    {
        if(func_num_args() === 0) {
            return $this->slug;
        }

        $this->slug = $slug;

        return $this;
    }

    /**
     * Handler
     *
     * @param array|callable|string|null $handler the controller
     *
     * @return RouteTemplate|array
     */
    public function handler($handler = null)
    {
        if(func_num_args() === 0) {
            return $this->handler;
        }

        $this->handler = $handler;

        return $this;
    }

    /**
     * Middleware
     *
     * @param array|string|null $middleware array of middleware classes to use for the route or string name of group
     *
     * @return RouteTemplate|array|null
     */
    public function middleware($middleware = null)
    {
        if(func_num_args() === 0) {
            return $this->middleware;
        }

        $this->middleware = is_array($middleware) ? $middleware : [$middleware];

        return $this;
    }

    /**
     * On Any Request
     *
     * Does not work with MIME-type templates
     *
     * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
     *
     * @param string $slug any valid template file name without .php extention
     * @param array|callable|string $handler the controller
     *
     * @return RouteTemplate
     */
    public function on(string $slug, $handler)
    {
        $scope = explode('-', $slug, 2);

        switch ($scope[0]) {
            case 'privacy' :
                $type = 'privacypolicy';
                break;
            case 'front' :
                $slug = 'front-page';
                $type = 'frontpage';
                break;
            default :
                $type = $scope[0];
                break;
        }

        $this->type($type);
        $this->slug($slug . '.php');
        $this->handler($handler);

        return $this;
    }

    /**
     * @return array|mixed|null
     */
    protected function getRequestHandler() {
        $method = Request::new()->getFormMethod();

        if($handler = $this->methods[$method] ?? null) {
            return $handler;
        }

        return $this->handler;
    }

    /**
     * Respond
     */
    public function respond()
    {
        $handler = $this->getRequestHandler();

        if(!$handler) {
            wp_die(
                __('Request method not supported for this template route.', 'typerocket-domain'),
                '',
                Response::getFromContainer()->getStatus() ?? []
            );
        }

        \TypeRocket\Pro\Http\Template::respond($handler, [], [], $this->middleware);
    }

    /**
     * Register
     *
     * @return $this
     */
    public function register()
    {
        TemplateRouter::getFromContainer()->routes->addRoute($this);

        return $this;
    }
}