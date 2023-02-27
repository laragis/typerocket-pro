<?php
namespace TypeRocket\Pro\Elements\Traits;

use TypeRocket\Pro\Elements\Fields\Background;
use TypeRocket\Pro\Elements\Fields\Checkboxes;
use TypeRocket\Pro\Elements\Fields\Editor;
use TypeRocket\Pro\Elements\Fields\Gallery;
use TypeRocket\Pro\Elements\Fields\Location;
use TypeRocket\Pro\Elements\Fields\Range;
use TypeRocket\Pro\Elements\Fields\Swatches;
use TypeRocket\Pro\Elements\Fields\Textexpand;
use TypeRocket\Pro\Elements\Fields\Url;
use TypeRocket\Pro\Elements\Fields\WordPressTimezone;

trait AdvancedFields
{
    /**
     * URL Input
     *
     * @param string $name
     * @param array $attr
     * @param array $settings
     * @param bool $label
     *
     * @return Url
     */
    public function url( $name, array $attr = [], array $settings = [], $label = true )
    {
        return new Url( $name, $attr, $settings, $label, $this );
    }

    /**
     * Range
     *
     * @param string $name
     * @param array $attr
     * @param array $settings
     * @param bool $label
     *
     * @return Range
     */
    public function range( $name, array $attr = [], array $settings = [], $label = true )
    {
        return new Range( $name, $attr, $settings, $label, $this );
    }

    /**
     * Textexpand Input
     *
     * @param string $name
     * @param array $attr
     * @param array $settings
     * @param bool $label
     *
     * @return Textexpand
     */
    public function textexpand( $name, array $attr = [], array $settings = [], $label = true )
    {
        return new Textexpand( $name, $attr, $settings, $label, $this );
    }

    /**
     * Checkboxes Input
     *
     * @param string $name
     * @param array $attr
     * @param array $settings
     * @param bool $label
     *
     * @return Checkboxes
     */
    public function checkboxes( $name, array $attr = [], array $settings = [], $label = true )
    {
        return new Checkboxes( $name, $attr, $settings, $label, $this );
    }

    /**
     * Background Input
     *
     * @param string $name
     * @param array $attr
     * @param array $settings
     * @param bool $label
     *
     * @return Background
     */
    public function background( $name, array $attr = [], array $settings = [], $label = true )
    {
        return new Background( $name, $attr, $settings, $label, $this );
    }

    /**
     * Gallery Input
     *
     * @param string $name
     * @param array $attr
     * @param array $settings
     * @param bool $label
     *
     * @return Gallery
     */
    public function gallery( $name, array $attr = [], array $settings = [], $label = true )
    {
        return new Gallery( $name, $attr, $settings, $label, $this );
    }

    /**
     * Swatches Input
     *
     * @param string $name
     * @param array $attr
     * @param array $settings
     * @param bool $label
     *
     * @return Swatches
     */
    public function swatches( $name, array $attr = [], array $settings = [], $label = true )
    {
        return new Swatches( $name, $attr, $settings, $label, $this );
    }

    /**
     * Location Inputs
     *
     * @param string $name
     * @param array $attr
     * @param array $settings
     * @param bool $label
     *
     * @return Location
     */
    public function location( $name, array $attr = [], array $settings = [], $label = true )
    {
        return new Location( $name, $attr, $settings, $label, $this );
    }

    /**
     * Editor Input
     *
     * @param string $name
     * @param array $attr
     * @param array $settings
     * @param bool $label
     *
     * @return Editor
     */
    public function editor( $name, array $attr = [], array $settings = [], $label = true )
    {
        return new Editor( $name, $attr, $settings, $label, $this );
    }

    /**
     * WordPress Timezone
     *
     * @param string $name
     * @param array $attr
     * @param array $settings
     * @param bool $label
     *
     * @return WordPressTimezone
     */
    public function wpTimezone( $name, array $attr = [], array $settings = [], $label = true )
    {
        return new WordPressTimezone( $name, $attr, $settings, $label, $this );
    }
}