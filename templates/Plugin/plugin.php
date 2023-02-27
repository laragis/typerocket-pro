<?php
/*
Plugin Name: {{name}}
Version: 1.0
Description: Boilerplate TypeRocket Plugin.
Author: TypeRocket Galaxy CLI
License: GPLv2 or later
*/

if ( !function_exists( 'add_action' ) ) {
    echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
    exit;
}

if(!defined('TYPEROCKET_PLUGIN___KEY___VIEWS_PATH')) {
    define('TYPEROCKET_PLUGIN___KEY___VIEWS_PATH', __DIR__ . '/resources/views');
}

$__typerocket_plugin___key__ = null;

function typerocket_plugin___key__() {
    global $__typerocket_plugin___key__;

    if($__typerocket_plugin___key__) {
        return;
    }

    if(file_exists(__DIR__ . '/vendor/autoload.php')) {
        require __DIR__ . '/vendor/autoload.php';
    } else {
        $map = [
            'prefix' => 'MyNamespace',
            'folder' => __DIR__ . '/app',
        ];

        typerocket_autoload_psr4($map);
    }

    $__typerocket_plugin___key__ = call_user_func('MyNamespace\MyClassTypeRocketPlugin::new', __FILE__, __DIR__);
}

register_activation_hook( __FILE__, 'typerocket_plugin___key__');
add_action('delete_plugin', 'typerocket_plugin___key__');
add_action('typerocket_loaded', 'typerocket_plugin___key__', 9);