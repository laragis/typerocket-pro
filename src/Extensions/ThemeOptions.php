<?php
namespace TypeRocket\Pro\Extensions;

use TypeRocket\Core\Config;
use TypeRocket\Models\WPOption;
use TypeRocket\Template\TemplateEngine;
use TypeRocket\Template\View;
use TypeRocket\Utility\Helper;

class ThemeOptions
{
    protected $name = 'tr_theme_options';

    public function __construct()
    {
        if(!Config::env('TYPEROCKET_THEME_OPTIONS', true)) {
            return;
        }

        add_action( 'typerocket_loaded', [$this, 'setup']);
    }

    public function getName()
    {
        return $this->name;
    }

    public function setup()
    {
        $this->name = apply_filters('typerocket_theme_options_name', $this->name );
        add_action( 'admin_menu', [$this, 'menu']);
        add_action( 'wp_before_admin_bar_render', [$this, 'admin_bar_menu'], 100 );
        add_filter('typerocket_model', [$this, 'fillable'], 9999999999 );
    }

    public function fillable( $model )
    {
        if ($model instanceof WPOption) {
            $model->mightNeedFillable($this->name);
        }
    }

    public function menu()
    {
        add_theme_page( 'Theme Options', 'Theme Options', 'edit_theme_options', 'theme_options', [$this, 'page']);
    }

    public function page()
    {
        echo '<div class="wrap">';
        $controller = apply_filters('typerocket_theme_options_controller', [$this, 'controller'] );
        $returned = call_user_func($controller, $this);

        if ( $returned instanceof View) {
            $returned->render();
        } elseif(file_exists($returned)) {
            /** @noinspection PhpIncludeInspection */
            include $returned;
        }

        echo '</div>';
    }

    /**
     * Controller
     *
     * @return View
     * @throws \Exception
     */
    public function controller()
    {
        /** @var \TypeRocket\Elements\BaseForm $form */
        $form = Helper::form(WPOption::class, 'update')->setMethod('PUT')->setGroup( $this->getName() );
        $view = View::new('extensions.theme-options', ['form' => $form])->setEngine(TemplateEngine::class);
        return apply_filters('typerocket_theme_options_view', $view);
    }

    public function add_sub_menu( $name, $link, $root_menu, $id, $meta = false )
    {
        /** @var \WP_Admin_Bar $wp_admin_bar */
        global $wp_admin_bar;
        if ( ! current_user_can( 'manage_options' ) || ! is_admin_bar_showing()) {
            return;
        }

        $wp_admin_bar->add_menu( [
            'parent' => $root_menu,
            'id'     => $id,
            'title'  => $name,
            'href'   => $link,
            'meta'   => $meta
        ]);
    }

    public function admin_bar_menu()
    {
        $this->add_sub_menu( "Theme Options", admin_url() . 'themes.php?page=theme_options', "site-name",
            "tr-theme-options" );
    }

}