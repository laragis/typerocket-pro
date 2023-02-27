<?php
namespace TypeRocket\Pro\Extensions;

class HidePostMenu
{

    public function __construct()
    {
        add_action( 'admin_menu', [$this, 'admin_menu']);
        add_action( 'admin_bar_menu', [$this, 'admin_bar'], 999 );
    }

    public function admin_menu()
    {
        remove_menu_page( 'edit.php' );
    }

    public function admin_bar()
    {
        /** @var $wp_admin_bar \WP_Admin_Bar */
        global $wp_admin_bar;
        $wp_admin_bar->remove_node( 'new-post' );
    }
    
}