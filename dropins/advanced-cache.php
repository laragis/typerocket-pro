<?php
/**
 * TypeRocket Middleware and Kernel are unused when
 * advanced-cache.php finds a cached page.
 */
defined( 'ABSPATH' ) || exit;
define( 'TYPEROCKET_ADVANCED_CACHE', true );

function typerocket_advanced_cache_drop_in()
{
    if(defined('TYPEROCKET_GALAXY')) {
        return;
    }

    $rapid_pages = defined('TYPEROCKET_RAPID_PAGES') ? constant('TYPEROCKET_RAPID_PAGES') : true;

    // {{ config }} // Do not remove this line

    if( !defined('TYPEROCKET_RAPID_PAGES_FOLDER_PATH') || !$rapid_pages ) {
        header( 'X-TypeRocket-Rapid-Pages-Error: "Missing TYPEROCKET_RAPID_PAGES_FOLDER_PATH or TYPEROCKET_RAPID_PAGES"' );
        return;
    }

    // Do not send cache for search result pages or dynamic pages
    if(!empty($_GET) || empty($_SERVER['REQUEST_URI']) ) {
        return;
    }

    $request_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $wants_slash = defined('TYPEROCKET_CACHE_WANTS_SLASH') ? constant('TYPEROCKET_CACHE_WANTS_SLASH') : true;
    $has_slash = strrpos($request_path, '/') !== 0;

    // Redirect if URL does not end with(out) slash as WordPress desires
    if( $has_slash != $wants_slash && $request_path !== '/' ) {
        return;
    }

    // Do not send cache if unwanted
    if( ($_SERVER['HTTP_X_NO_CACHE'] ?? 'no') == 'yes' ) {
        return;
    }

    // Do not send cache for logged-in users.
    // Note: wp_get_current_user() is not defined
    if(!empty($_COOKIE)) {
        foreach ($_COOKIE as $name => $content) {
            if (strpos($name, 'wordpress_logged_in_') === 0) {
                return;
            }
        }
    }

    // Send Rapid Pages' cache
    if(!$path = trim($request_path, '/')) {
        $path = 'index';
    }

    $cache = rtrim(TYPEROCKET_RAPID_PAGES_FOLDER_PATH, '\\/') . "/{$path}.html";

    if(is_file($cache)) {
        $time = gmdate('D, d M Y H:i:s ', filemtime($cache)) . 'GMT';

        header("Last-Modified: {$time}");
        header("X-TypeRocket-Rapid-Pages-Cache: yes");
        readfile($cache);
        exit();
    }
}

typerocket_advanced_cache_drop_in();