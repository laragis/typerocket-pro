<?php
namespace TypeRocket\Pro\Controllers;

use TypeRocket\Database\Results;
use TypeRocket\Models\WPPost;
use TypeRocket\Controllers\Controller;

class TemplateController extends Controller
{
    /** @var Results|null */
    protected $posts;
    protected $resultsClass = Results::class;
    protected $modelClass = WPPost::class;

    /**
     * @param null|string $modelClass
     * @param null|string $resultsClass
     *
     * @return Results|null
     */
    public function buildPosts($modelClass = null, $resultsClass = null)
    {
        /** @var \WP_Query $wp_query */
        global $wp_query;

        if($wp_query->posts) {
            $resultsClass = $resultsClass ?? $this->resultsClass;
            $this->posts = new $resultsClass;
            $this->posts->exchangeAndCast($wp_query->posts, $modelClass ?? $this->posts->class ?? $this->modelClass);
        }

        return $this->posts;
    }
}
