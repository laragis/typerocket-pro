<?php
namespace TypeRocket\Pro\Template;

use TypeRocket\Core\Config;
use TypeRocket\Template\TemplateEngine;

class TachyonTemplateEngine extends TemplateEngine
{
    /**
     * Load View
     *
     * @param string $dots
     * @param array $_tr_data
     * @param string|null $ext
     */
    public function include($dots, $_tr_data = [], $ext = null) {
        $ext = $ext ?? $this->ext ?? '.php';

        if(file_exists($dots)) {
            $_tr_view_file = $dots;
        } else {
            $_tr_view_file = str_replace('.', '/' , $dots) . $ext;
            $_tr_view_file = $this->folder . '/' . $_tr_view_file;
        }

        $cb = \Closure::bind(function() use ($_tr_view_file, $_tr_data) {

            extract($this->getData());
            extract($_tr_data);
            unset($_tr_data);

            if(file_exists($_tr_view_file)) {
                include $_tr_view_file;
            }

        }, $this);

        $cb();
    }

    /**
     * @param bool $condition
     * @param mixed ...$args
     *
     * @return bool
     */
    public function includeIf($condition, ...$args)
    {
        if($condition) {
            $this->include(...$args);
        }

        return $condition;
    }

    /**
     * @param string $dots
     * @param array $data
     * @param null|string $name
     * @param string $ext
     */
    public function header($dots, $data = [], $name = null, $ext = null)
    {
        do_action( 'get_header', $name ?? str_replace('.', '-', $dots) );

        $this->include($dots, $data, $ext);
    }

    /**
     * @param string $dots
     * @param array $data
     * @param null|string $name
     * @param string $ext
     */
    public function footer($dots, $data = [], $name = null, $ext = null)
    {
        do_action( 'get_footer', $name ?? str_replace('.', '-', $dots) );

        $this->include($dots, $data, $this->ext);
    }

    /**
     * Start Section
     *
     * @param string $name
     */
    public function section($name)
    {
        $this->currentSection = $name;
        ob_start();
    }

    /**
     * End Section
     */
    public function end()
    {
        $this->sections[$this->currentSection] = ob_get_clean();
    }

    /**
     * Yield Section
     *
     * @param string $section
     */
    public function yield($section)
    {
        echo $this->sections[$section] ?? null;
    }

    /**
     * @param string $layout
     */
    public function layout($layout)
    {
        $this->layout = $layout;
        ob_start();
    }

    /**
     * Load Template
     */
    public function load()
    {
        extract( $this->data );
        /** @noinspection PhpIncludeInspection */
        include ( $this->file );

        if($this->layout) {
            $html = trim(ob_get_clean());

            if(empty($this->sections['main'])) {
                $this->sections['main'] = $html;
            } elseif($html) {
                $this->sections['__main'] = $html;
            }

            $this->include($this->layout);
        }
    }
}