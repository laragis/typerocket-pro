<?php
namespace TypeRocket\Pro\Core;

use TypeRocket\Elements\Notice;
use TypeRocket\Utility\Manifest;

class AdvancedSystem
{
    public function __construct()
    {
        add_action('wp_update_nav_menu_item', 'TypeRocket\Http\Responders\Hook::menus', 10, 3 );
        add_filter('typerocket_repeater_item_controls', [$this, 'addCloneToRepeatable']);
        add_filter('typerocket_component_item_controls', [$this, 'addCloneToRepeatable']);
        add_action('typerocket_top_assets', [$this, 'topAssets'], 10, 2);
    }

    public function addCloneToRepeatable($list)
    {
        $list['clone'] = [
            'class' => 'tr-repeater-clone tr-control-icon tr-control-icon-clone',
            'title' => __('Duplicate', 'typerocket-domain'),
            'tabindex' => '0'
        ];

        return $list;
    }

    public function topAssets($url, $manifest)
    {
        if(empty(Manifest::typerocket()['/js/pro-core.js'])) {
            $this->adminNotice();
            return;
        }

        add_action('typerocket_bottom_assets', [$this, 'bottomAssets'], 10, 2);

        wp_enqueue_style( 'typerocket-styles-redactor', $url . $manifest['/css/redactor.css']);
    }

    public function bottomAssets($url, $manifest)
    {
        wp_enqueue_script( 'typerocket-scripts-advanced', $url . $manifest['/js/pro-core.js'], [ 'jquery', 'wp-i18n' ], false, true );
    }

    public function adminNotice()
    {
        if(!is_admin()) {
            return;
        }

        $exception = __('Some TypeRocket Pro features are disabled. You need to publish your Pro assets with the galaxy CLI command: php galaxy extension:publish typerocket/professional.', 'typerocket-domain');
        Notice::admin(['type' => 'error', 'message' => $exception]);
    }
}