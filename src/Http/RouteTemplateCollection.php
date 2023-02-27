<?php
namespace TypeRocket\Pro\Http;

class RouteTemplateCollection
{
    protected $active = [];
    protected $slugs = [];
    protected $force = false;
    protected $templates = [];
    protected $found;
    /** @var RouteTemplate[]  */
    protected $routes = [];
    protected $hierarchy = [];

    /**
     * Only Allow Route Templates
     *
     * @param bool $force
     */
    public function forceRouteTemplates($force = true)
    {
        $this->force = $force;
    }

    /**
     * Add Route
     *
     * Types include: index, 404, archive, author, category, tag, taxonomy, date,
     * embed, home, frontpage, privacypolicy, page, paged, search, single,
     * singular, attachment
     *
     * @param string $route
     *
     * @return $this
     */
    public function addRoute($route)
    {
        $this->routes[] = $route;

        return $this;
    }

    /**
     * @return $this
     */
    public function compileRouteHierarchy()
    {
        foreach ($this->routes as $route) {
            $slug = $route->slug();
            $this->hierarchy[$route->type()][] = $slug;
            $this->slugs[$slug] = $route;
        }

        return $this;
    }

    /**
     * @param $template
     *
     * @return mixed
     */
    public function runFoundTemplate($template)
    {
        /** @var null|RouteTemplate $route */
        if($route = $this->slugs[$this->found] ?? null) {
            $route->respond();
        }

        return $template;
    }

    /**
     * @param $templates
     *
     * @return array|mixed
     */
    public function blockTemplates($templates)
    {
        $this->templates = $templates;

        return $this->force ? [] : $templates;
    }

    /**
     * @param $current
     * @param $type
     *
     * @return string
     */
    public function process($current, $type)
    {
        $templates = $this->hierarchy[$type] ?? [];

        foreach ($this->templates as $temp) {
            if(in_array($temp, $templates)) {
                $this->found = $temp;

                return __DIR__ . '/temp/route-template.php';
            }
        }

        return $current;
    }

    /**
     * Detect Template
     *
     * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#filter-hierarchy
     */
    public function detectTemplateRoute()
    {
        $this->compileRouteHierarchy();

        foreach ($this->hierarchy as $type => $options) {
            add_filter( "{$type}_template_hierarchy", [$this, 'blockTemplates'] );
            add_filter( "{$type}_template", [$this, 'process'], 10, 2);
            add_filter( "template_include", [$this, 'runFoundTemplate'], 100);
        }
    }
}