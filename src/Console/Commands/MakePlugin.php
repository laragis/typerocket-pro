<?php
namespace TypeRocket\Pro\Console\Commands;

use TypeRocket\Console\Command;
use TypeRocket\Utility\File;
use TypeRocket\Utility\Helper;
use TypeRocket\Utility\Sanitize;
use TypeRocket\Utility\Str;

class MakePlugin extends Command
{
    protected $command = [
        'make:plugin',
        'Make new WP plugin',
        'This command makes new WP plugin.',
    ];

    protected function config()
    {
        $this->addArgument('namespace', self::REQUIRED, 'The PHP namespace of the plugin.');
        $this->addArgument('name', self::REQUIRED, 'The name of the plugin in quotes.');
    }

    /**
     * Execute Command
     *
     * @return int|null|void
     * @throws \Exception
     */
    protected function exec()
    {
        $namespace = $this->getClassArgument('namespace');
        $name = $this->getArgument('name');
        $this->makeFile($name, $namespace);
    }

    /**
     * Make file
     *
     * @param string $class
     *
     * @throws \Exception
     */
    protected function makeFile($name, $namespace)
    {
        $key = Sanitize::underscore($name);
        $class = Str::camelize($key);
        $slug = Sanitize::dash($name);
        $package = strtolower(Sanitize::dash(Str::snake($namespace))."/{$name}");
        $plugin_path = WP_PLUGIN_DIR . '/' . $slug;

        if($this->continue("Clone GALAXY CLI for {$name} to project root? (y/n)")) {

            $abs = ABSPATH;
            $root = $abs;

            if(Str::starts(TYPEROCKET_PATH, ABSPATH)) {
                $root = TYPEROCKET_PATH . '/';
            }

            $galaxy = $root . 'galaxy_' . $key;
            $config = "galaxy-{$slug}-config.php";
            $typerocket = TYPEROCKET_PATH;

            File::new(TYPEROCKET_GALAXY_FILE)->copyTo($galaxy);
            File::new($galaxy)->replaceTemplateContent(['galaxy-config.php'], ["galaxy-{$slug}-config.php"] );
            File::new($root . $config)->create("<?php
\$typerocket = '{$typerocket}';
\$overrides = '{$plugin_path}';
define('TYPEROCKET_GALAXY_MAKE_NAMESPACE', '{$namespace}');
define('TYPEROCKET_GALAXY_PATH', \$typerocket);
define('TYPEROCKET_CORE_CONFIG_PATH', \$typerocket . '/config' );
define('TYPEROCKET_ROOT_WP', '{$abs}');

define('TYPEROCKET_APP_ROOT_PATH', \$overrides);
define('TYPEROCKET_ALT_PATH', \$overrides);");

            $this->success('GALAXY CLI clone: ' . $galaxy);
        }

        if( file_exists($plugin_path) ) {
            $this->error('Plugin ' . $name . ' not created because it already exists.');
            return;
        }

        $tags = ['{{name}}', 'MyClass', 'MyNamespace', '{{slug}}', '{{key}}','{{package}}', '__key__', '__KEY__'];
        $replacements = [ $name, $class, $namespace, $slug, $key, $package, $key, strtoupper($key) ];

        File::new(__DIR__ . '/../../../templates/Plugin')->copyTo($plugin_path);

        $plugin_file = File::new($plugin_path . '/plugin.php')->replaceTemplateContent($tags, $replacements );
        File::new($plugin_path . '/app/MyClassTypeRocketPlugin.php')->replaceTemplateContent($tags, $replacements );
        File::new($plugin_path . '/app/View.php')->replaceTemplateContent($tags, $replacements );
        File::new($plugin_path . '/resources/views/settings.php')->replaceTemplateContent($tags, $replacements );
        File::new($plugin_path . '/composer.json')->replaceTemplateContent($tags, $replacements );
        File::new($plugin_path . '/uninstall.php')->replaceTemplateContent($tags, $replacements );

        File::new($plugin_path . '/plugin.php')->rename($slug . '.php');
        File::new($plugin_path . '/app/MyClassTypeRocketPlugin.php')->rename($class . 'TypeRocketPlugin.php', false);

        if($plugin_file->exists()) {
            $this->success('Plugin "' . $name . '" with app/' . $class . 'TypeRocketPlugin.php created at ' . $plugin_path);
        } else {
            $this->error('Plugin ' . $name . ' not created.');
        }
    }
}