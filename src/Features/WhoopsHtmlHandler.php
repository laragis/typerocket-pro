<?php
namespace TypeRocket\Pro\Features;

use Whoops\Handler\PrettyPageHandler;

class WhoopsHtmlHandler extends PrettyPageHandler
{
    /**
     * @return int|void|null
     * @throws \Exception
     */
    public function __construct() {
        ob_start(); // block WP output

        parent::__construct();
    }

    /**
     * @return int|void|null
     * @throws \Exception
     */
    public function handle() {
        define('WHOOPS_WP_HTML_ERROR_HANDLED', true);

        if(function_exists('get_post')) {
            $tables = [
                '$wp'       => function () {
                    global $wp;
                    if ( ! $wp instanceof \WP ) {
                        return [];
                    }
                    $output = get_object_vars( $wp );
                    unset( $output['private_query_vars'] );
                    unset( $output['public_query_vars'] );
                    return array_filter( $output );
                },
                '$wp_query' => function () {
                    global $wp_query;
                    if ( ! $wp_query instanceof \WP_Query ) {
                        return [];
                    }
                    $output               = get_object_vars( $wp_query );
                    $output['query_vars'] = array_filter( $output['query_vars'] );
                    unset( $output['posts'] );
                    unset( $output['post'] );
                    return array_filter( $output );
                },
                '$post'     => function () {
                    $post = get_post();
                    if ( ! $post instanceof \WP_Post ) {
                        return [];
                    }
                    return get_object_vars( $post );
                },
            ];

            $tables = apply_filters('typerocket_whoops_tables', $tables);

            foreach ( $tables as $name => $callback ) {
                $this->addDataTableCallback( $name, $callback );
            }
        }

        return parent::handle();
    }
}