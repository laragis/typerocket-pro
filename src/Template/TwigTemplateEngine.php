<?php
namespace TypeRocket\Pro\Template;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use TypeRocket\Core\Config;
use TypeRocket\Template\TemplateEngine;

class TwigTemplateEngine extends TemplateEngine
{
    /**
     * Load Template
     */
    public function load()
    {
        $name = basename($this->file, '.php');

        $debug = Config::get('app.debug');
        $cache = $debug ? false : Config::get('paths.cache') . '/twig';

        $env = Config::get('twig.env', [
            'debug' => $debug,
            'cache' => $cache,
        ]);

        $loader = new FilesystemLoader( dirname($this->file) );
        $twig = new Environment($loader, $env);

        $template = $twig->load( $name . '.twig' );
        echo $template->render($this->data);
    }
}