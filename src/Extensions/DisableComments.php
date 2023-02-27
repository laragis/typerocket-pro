<?php
namespace TypeRocket\Pro\Extensions;

class DisableComments
{

    public function __construct()
    {
        add_action('admin_init', [$this, 'admin_menu_redirect']);
        add_action('admin_init', [$this, 'post_types_support']);
        add_filter('comments_open', [$this, 'status'], 20, 2);
        add_filter('pings_open', [$this, 'status'], 20, 2);
        add_filter('comments_array', [$this, 'hide_existing_comments'], 10, 2);
        add_action('admin_menu', [$this, 'admin_menu']);
        add_action('admin_init', [$this, 'dashboard']);
        add_action('init', [$this, 'admin_bar']);
        add_action('wp_before_admin_bar_render', [$this, 'admin_bar_top']);
    }

    // Disable support for comments and trackbacks in post types
    public function post_types_support()
    {
        $post_types = get_post_types();
        foreach ($post_types as $post_type) {
            if (post_type_supports($post_type, 'comments')) {
                remove_post_type_support($post_type, 'comments');
                remove_post_type_support($post_type, 'trackbacks');
            }
        }
    }

    // Close comments on the front-end
    public function status()
    {
        return false;
    }


    // Hide existing comments
    public function hide_existing_comments($comments)
    {
        $comments = array();
        return $comments;
    }

    // Remove comments page in menu
    public function admin_menu()
    {
        remove_menu_page('edit-comments.php');
    }

    // Redirect any user trying to access comments page
    public function admin_menu_redirect()
    {
        global $pagenow;
        if ($pagenow === 'edit-comments.php') {
            wp_redirect(admin_url());
            exit;
        }
    }

    // Remove comments metabox from dashboard
    public function dashboard()
    {
        remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');
    }


    // Remove comments links from admin bar
    public function admin_bar()
    {
        if (is_admin_bar_showing()) {
            remove_action('admin_bar_menu', 'wp_admin_bar_comments_menu', 60);
        }
    }

    // Remove comments links from admin bar top
    public function admin_bar_top()
    {
        /** @var \WP_Admin_Bar $wp_admin_bar */
        global $wp_admin_bar;
        $wp_admin_bar->remove_menu('comments');
    }


}