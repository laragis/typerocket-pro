<?php
namespace TypeRocket\Pro\Register;

use TypeRocket\Database\Migrate;
use TypeRocket\Exceptions\MigrationException;
use TypeRocket\Http\Response;
use TypeRocket\Register\Page;

abstract class BasePlugin
{
    protected $title;
    protected $slug;
    protected $name;
    protected $migrationKey;
    /**
     * @var bool|Migrate
     */
    protected $migrations = false;
    protected $file;
    protected $path;
    /** @var static */

    /**
     * @param string $file
     * @param string $path
     *
     * @return static
     */
    public static function new($file, $path)
    {
        return new static($file, $path);
    }

    /**
     * Boilerplate
     */
    public function init() {}

    /**
     * BasePlugin constructor.
     *
     * @param string $file
     * @param string $path
     */
    public function __construct($file, $path)
    {
        $this->file = $file;
        $this->name = $name = plugin_basename($file);
        $this->path = $path;
        $migrationsPath = $this->path . '/database/migrations';

        if(file_exists($migrationsPath)) {
            $migrate = new Migrate($migrationsPath, null, $this->migrationKey );
            $this->migrations = $this->migrations ? $migrate : false;
        }

        $this->init();

        if($this->migrations instanceof Migrate) {
            add_filter('typerocket_dev_migration_folders', function ($folders) {
                $folders[$this->migrationKey] = $this->migrations->getFolder();
                return $folders;
            });
        }

        if(method_exists($this, 'routes')) {
            add_action('typerocket_routes', [$this, 'routes']);
        }

        if(method_exists($this, 'policies')) {
            add_filter('typerocket_auth_policies', function($policies) {
                return array_merge($this->policies(), $policies);
            });
        }

        register_deactivation_hook( $this->file, function() {
            $this->deactivate();
        });

        if(defined('WP_UNINSTALL_PLUGIN') && defined('TYPEROCKET_PLUGIN_UNINSTALL')) {
            if(TYPEROCKET_PLUGIN_UNINSTALL === $this->slug) {
                $this->uninstall();
            }
        }

        if(did_action('activate_'.$this->name)) {
            $this->activate();
        }
    }

    /**
     * @param $callback
     */
    public function inlinePluginLinks($callback)
    {
        add_filter('plugin_action_links', function($actions, $plugin_file) use ($callback) {

            if( $found = strpos($this->file, $plugin_file) ) {
                $actions = array_merge($actions, $callback());
            }

            return $actions;
        }, 10, 2 );
    }

    /**
     * @param mixed ...$args
     *
     * @return Page
     */
    public function pluginSettingsPage(...$args)
    {
        return Page::add('settings', $this->slug, $this->title, ...$args);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $folder
     *
     * @return array|mixed
     */
    public function manifest($folder = 'public')
    {
        return \TypeRocket\Utility\Manifest::cache(
            $this->path . '/' . trim($folder, DIRECTORY_SEPARATOR) . '/mix-manifest.json',
            'typerocket-plugin-' . $this->slug
        );
    }

    /**
     * @param null|string $with
     *
     * @return string
     */
    public function uri($with = null)
    {
        $uri = plugin_dir_url($this->file);
        return trim(($with ? $uri . $with : $uri), '/');
    }

    /**
     * @throws \Exception
     */
    public function migrateUp()
    {
        if(!$this->migrations instanceof Migrate) {
            return;
        }

        try {
            $result = $this->migrations->runMigrationDirectory('up', 9999999999);
        } catch (MigrationException $e) {
            try {
                $result = $this->migrations->runMigrationDirectory('up', 9999999999);
            } catch (MigrationException $e) {

                Response::getFromContainer()->flashNow($e->getMessage(), 'warning');
                return;
            }
        }

        Response::getFromContainer()->flashNow($result['message'], 'success');
    }

    /**
     * @throws \Exception
     */
    public function migrateDown()
    {
        if(!$this->migrations instanceof Migrate) {
            return;
        }

        try {
            $this->migrations->runMigrationDirectory('down', 9999999999);
        } catch (MigrationException $e) {
        }
    }

    abstract public function activate();
    abstract public function deactivate();
    abstract public function uninstall();
}