<?php
namespace MyNamespace;

use TypeRocket\Core\System;
use TypeRocket\Utility\Helper;
use TypeRocket\Pro\Register\BasePlugin;
use MyNamespace\View;

class MyClassTypeRocketPlugin extends BasePlugin
{
    protected $title = '{{name}}';
    protected $slug = '{{slug}}';
    protected $migrationKey = '{{key}}_migrations';
    protected $migrations = true;

    public function init()
    {
        // Plugin Settings
        $page = $this->pluginSettingsPage([
            'view' => View::new('settings', [
                'form' => Helper::form()->setGroup('{{key}}_settings')->useRest()
            ])
        ]);

        $this->inlinePluginLinks(function() use ($page) {
            return [
                'settings' => "<a href=\"{$page->getUrl()}\" aria-label=\"Settings\">Settings</a>"
            ];
        });

        // Assets Manifest
        $manifest = $this->manifest('public');
        $uri = $this->uri('public');

        // Front Assets
        add_action('wp_enqueue_scripts', function() use ($manifest, $uri) {
            wp_enqueue_style( 'main-style-' . $this->slug, $uri . $manifest['/front/front.css'] );
            wp_enqueue_script( 'main-script-' . $this->slug, $uri . $manifest['/front/front.js'], [], false, true );
        });

        // Admin Assets
        add_action('admin_enqueue_scripts', function() use ($manifest, $uri) {
            wp_enqueue_style( 'admin-style-' . $this->slug, $uri . $manifest['/admin/admin.css'] );
            wp_enqueue_script( 'admin-script-' . $this->slug, $uri . $manifest['/admin/admin.js'], [], false, true );
        });

        // TODO: Add your init code here
    }

    public function routes()
    {
        // TODO: Add your TypeRocket route code here
    }

    public function policies()
    {
        // TODO: Add your TypeRocket policies here
        return [

        ];
    }

    public function activate()
    {
        $this->migrateUp();
        System::updateSiteState('flush_rewrite_rules');

        // TODO: Add your plugin activation code here
    }

    public function deactivate()
    {
        // Migrate `down` only on plugin uninstall
        System::updateSiteState('flush_rewrite_rules');

        // TODO: Add your plugin deactivation code here
    }

    public function uninstall()
    {
        $this->migrateDown();

        // TODO: Add your plugin uninstall code here
    }
}