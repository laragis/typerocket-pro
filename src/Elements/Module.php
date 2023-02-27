<?php
namespace TypeRocket\Pro\Elements;

abstract class Module
{
    protected $props;
    protected $slots;

    /**
     * @param $props
     * @param mixed ...$slots
     *
     * @return static
     */
    public static function new($props, ...$slots)
    {
        return new static($props, ...$slots);
    }

    /**
     * Module constructor.
     *
     * @param $props
     * @param mixed ...$slots
     */
    public function __construct($props, ...$slots)
    {
        $this->props = $props;
        $this->slots = $slots;
    }

    /**
     * @return false|string
     */
    public function __toString()
    {
        ob_start();
        $this->render($this->props, ...$this->slots);
        return ob_get_clean();
    }

    abstract function render($props, $slot = null);

}
