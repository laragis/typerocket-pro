<?php
namespace TypeRocket\Pro\Extensions\Seo;

use TypeRocket\Utility\ModelField;

class MetaData
{
    public $title = null;
    public $itemId = null;
    public $meta = null;
    public $url = null;

    public function setup()
    {
        add_filter( 'jetpack_enable_opengraph', '__return_false', 99 );
        add_action( 'wp_head', [$this, 'head_data'], 1 );
        add_action( 'template_redirect', [$this, 'loaded'], 0 );
        add_filter( 'document_title_parts', [$this, 'title'], 100, 3 );
        remove_action( 'wp_head', 'rel_canonical' );
        add_action( 'wp', [$this, 'redirect'], 99, 1 );
    }

    public function loaded()
    {
        $this->itemId = (int) get_queried_object_id();
    }

    // Page Title
    public function title( $title, $arg2 = null, $arg3 = null )
    {
        $meta = ModelField::post( 'seo.meta', $this->itemId );
        $meta = apply_filters('typerocket_seo_meta', $meta);

        $url = get_the_permalink($this->itemId);
        $url = apply_filters('typerocket_seo_url', $url);

        $this->meta = $meta;
        $this->url = $url;

        $newTitle = trim( $this->meta['basic']['title'] ?? '' );

        if ( !empty($newTitle) ) {
            $this->title = $newTitle;
            return [$newTitle];
        } else {
            $this->title = $title;
            return $title;
        }

    }

    // Set Title Tag
    public function title_tag()
    {
        echo '<title>' . $this->title( '|', false, 'right' ) . "</title>";
    }

    // Get Last Valid Item
    public function getLastValidItem(array $options, $callback = 'esc_attr')
    {
        $result = null;
        foreach ($options as $option) {
            if(!empty($option)) {
                $value = call_user_func($callback, trim($option));

                if(!empty($value)) {
                    $result = $value;
                }
            }
        }

        return $result;
    }

    // 301 Redirect
    public function redirect()
    {
        if ( is_singular() && !empty($this->meta['advanced']['redirect'] ?? null) ) {
            wp_redirect( $this->meta['advanced']['redirect'], 301 );
            exit;
        }
    }

    // head meta data
    public function head_data()
    {
        if(empty($this->meta['basic'])) {
            rel_canonical();
            return;
        }

        do_action('typerocket_seo_head', $this);

        // Vars
        $url        = $this->url;
        $seo        = $this->meta;
        $desc       = esc_attr__( $seo['basic']['desc'] ?? $seo['basic']['description'], 'typerocket-ext-seo' );
        $ogMeta = $twMeta = [];

        // Images
        $img = !empty($seo['og']['img']) ? wp_get_attachment_image_src( (int) $seo['og']['img'], 'full')[0] : null;

        // Basic
        $basicMeta['description'] = $desc;

        // OG
        if( !empty($seo['og']) ) {
            $ogMeta['og:type']        = $this->getLastValidItem([ is_front_page() ? 'website' : 'article' , $seo['og']['type'] ?? null ]);
            $ogMeta['og:title']       = esc_attr__( $seo['og']['title'] ?? null, 'typerocket-ext-seo' );
            $ogMeta['og:description'] = esc_attr__( $seo['og']['desc'] ?? $seo['og']['description'] ?? null, 'typerocket-ext-seo' );
            $ogMeta['og:url']         = $url;
            $ogMeta['og:image']       = $img;
        }

        // Canonical
        $canon            = esc_attr( $seo['advanced']['canonical'] ?? null );

        // Robots
        $robots  = array_map('esc_attr', $seo['robots'] ?? [] );

        // Twitter
        if( !empty($seo['tw']) ) {
            $twMeta['twitter:card']        = esc_attr( $seo['tw']['card'] ?? null);
            $twMeta['twitter:title']       = esc_attr__( $seo['tw']['title'] ?? null, 'typerocket-ext-seo' );
            $twMeta['twitter:description'] = esc_attr__( $seo['tw']['desc'] ?? $seo['tw']['description'] ?? null, 'typerocket-ext-seo' );
            $twMeta['twitter:site']        = esc_attr( $seo['tw']['site'] ?? null, 'typerocket-ext-seo' );
            $twMeta['twitter:image']       = !empty($seo['tw']['img']) ? wp_get_attachment_image_src( (int) $seo['tw']['img'], 'full')[0] : null;
            $twMeta['twitter:creator']     = esc_attr($seo['tw']['creator'] ?? null);
        }

        // Basic
        foreach ($basicMeta as $basicName => $basicContent) {
            if(!empty($basicContent)) {
                echo "<meta name=\"{$basicName}\" content=\"{$basicContent}\" />";
            }
        }

        // Canonical
        if ( ! empty( $canon ) ) {
            echo "<link rel=\"canonical\" href=\"{$canon}\" />";
        } else {
            rel_canonical();
        }

        // Robots
        if ( ! empty( $robots ) ) {
            $robot_data = '';
            foreach ( $robots as $value ) {
                if ( ! empty( $value ) && $value != 'none' ) {
                    $robot_data .= $value . ', ';
                }
            }

            $robot_data = mb_substr( $robot_data, 0, - 2 );
            if ( ! empty( $robot_data ) ) {
                echo "<meta name=\"robots\" content=\"{$robot_data}\" />";
            }
        }

        // OG
        foreach ($ogMeta as $ogName => $ogContent) {
            if(!empty($ogContent)) {
                echo "<meta property=\"{$ogName}\" content=\"{$ogContent}\" />";
            }
        }

        // Twitter
        foreach ($twMeta as $twName => $twContent) {
            if(!empty($twContent)) {
                echo "<meta name=\"{$twName}\" content=\"{$twContent}\" />";
            }
        }
    }
}