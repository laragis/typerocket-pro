<?php
namespace TypeRocket\Pro\Extensions;

class GutenbergRemover
{
    /**
     * @var bool|array
     */
    public $postTypes;

    public function __construct()
    {
        $this->postTypes = apply_filters('typerocket_ext_gutenberg_post_types', true);

        if(is_array($this->postTypes)) {
            add_filter('use_block_editor_for_post_type', [$this, 'select'], 11, 2);
        } elseif(!$this->postTypes) {
            add_filter( 'use_block_editor_for_post_type', '__return_false' );
            add_action( 'wp_enqueue_scripts', [$this, 'remove'], 101 );
        }
    }

    public function select($value, $type) {

        if( in_array($type, $this->postTypes) && $value ) {
            return true;
        }

        add_action( 'wp_enqueue_scripts', [$this, 'remove'], 101 );

        return false;
    }

    public function remove()
    {
        wp_dequeue_style( 'wp-block-library' );
    }

}