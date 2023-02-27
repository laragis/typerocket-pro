<?php
namespace TypeRocket\Pro\Extensions;

use TypeRocket\Core\Config;

class Seo
{
    protected $postTypes;

    public function __construct($post_types = null)
    {
        if(!Config::env('TYPEROCKET_SEO', true)) {
            return;
        }

        $this->postTypes = apply_filters('typerocket_ext_seo_post_types', $post_types);
        add_action( 'typerocket_loaded', [$this, 'setup']);
    }
    public function setup()
    {
        if ( ! defined( 'WPSEO_VERSION' ) && ! defined( 'AIOSEOP_VERSION' ) ) {
            define( 'TYPEROCKET_SEO_EXTENSION', true );
            (new Seo\PostTypeMeta())->setup($this->postTypes);
            (new Seo\MetaData())->setup();
        }
    }
}