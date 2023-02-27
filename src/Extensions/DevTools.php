<?php
namespace TypeRocket\Pro\Extensions;

use TypeRocket\Auth\Roles;
use TypeRocket\Core\Config;
use TypeRocket\Elements\Dashicons;
use TypeRocket\Html\Html;
use TypeRocket\Html\Element;
use TypeRocket\Http\Request;
use TypeRocket\Http\RouteCollection;
use TypeRocket\Register\Page;
use TypeRocket\Utility\RuntimeCache;
use TypeRocket\Utility\Str;

class DevTools
{
    public $toolbar = true;

    /**
     * DevTools constructor.
     *
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        if(!$request->isGet() || !Config::env('TYPEROCKET_DEV', true) ) {
            return;
        }

        $this->toolbar = Config::env('TYPEROCKET_DEV_TOOLBAR', true);

        add_action('typerocket_loaded', [$this, 'setup']);

        add_action('typerocket_routes', function() {
            \TypeRocket\Http\Route::new()->get()->match('tr-dev/tools/flush-rules')->do(function() {
               flush_rewrite_rules(true);
               return \TypeRocket\Http\Redirect::new()->back();
            });
        });

        if($this->showToolbar($request)) {
            $this->toolbar();
        }
    }

    public function showToolbar(Request $request)
    {
        if(!$this->toolbar) {
            return false;
        }

        if(Config::env('WP_DEBUG', false) == false) {
            return false;
        }

        if($request->isAjax()) {
            return false;
        }

        if($request->getHeader('X-Dev-Tools') == 'off') {
            return false;
        }

        if($request->getDataGet('tr_dev_tools') == 'off') {
            return false;
        }

        if($request->getDataGet('legacy-widget-preview')) {
            return false;
        }

        if(php_sapi_name() === 'cli') {
            return false;
        }

        return true;
    }

    public function setup()
    {
        add_filter('admin_footer_text', [$this, 'footer']);
        $settings = [
            'view' => [$this, 'view'],
            'menu' => __('Dev', 'typerocket-ext-dev')
        ];

        (new Page('TypeRocket', __('Dev'), __('TypeRocket Developer Tools', 'typerocket-ext-dev'), $settings))
            ->addToRegistry()->setIcon('dashicons-code-standards')->setPosition(99);
    }

    public function toolbar()
    {
        add_action('shutdown', [$this, 'footerTools'] );
        add_filter('admin_body_class', function($body_class) { $body_class .= ' tr-dev-toolbar'; return $body_class; });
        add_filter('body_class', function($classes) { array_push($classes, 'tr-dev-toolbar'); return $classes; });
    }

    public function footer($text)
    {
        echo $text . ' ' . __('TypeRocket Dev Tools Enabled', 'typerocket-ext-dev');
    }

    public function view(Page $page)
    {
        $icons = function()
        {
            echo '<div class="tr-p-20">';
            $icons = Dashicons::new()->iconNames();
            echo Element::title(__('Icons', 'typerocket-ext-dev'), 'dashicons-admin-generic');
            echo '<p>' . __('Icons can be used with post types, tabs, and pages. <a target="_blank" href="https://developer.wordpress.org/resource/dashicons/">See all dashicons</a>.', 'typerocket-ext-dev');
            echo '</p><p><input type="search" onkeyup="window.trUtil.list_filter(\'#dev-icon-search\', \'#debug-icon-list li\')" placeholder="' . __('Enter text to search list...') . '" id="dev-icon-search" /></p><ol id="debug-icon-list">';
            foreach ($icons as $i => $v) {
                echo Html::li(
                    ['data-search' => $v],
                    "<i class='tr-debug-icon dashicons {$v}'></i><strong>{$v}</strong>"
                );
            }
            echo '</ol>';
            echo '</div>';
        };

        $rules = function() {
            echo '<div class="tr-p-20">';
            echo Element::title(__('Rewrite Rules & Routes', 'typerocket-ext-dev'), 'dashicons-admin-site-alt3');
            $rules = get_option('rewrite_rules');
            $routes = RouteCollection::getFromContainer();
            if($routes->routes && $rules) {
                echo "<p><strong>TypeRocket Routes</strong>: If you are using TypeRocket custom routes they will appear in this list. Some routes may not appear in the list if they were conditionally registered.</p>";
                echo '<table class="wp-list-table widefat fixed striped">';
                echo "<thead><tr><th>" . __('Match', 'typerocket-ext-dev') . "</th><th>" . __('Vars', 'typerocket-ext-dev') . "</th><th class='tr-w-10'>" . __('Trailing Slash', 'typerocket-ext-dev') . "</th><th>" . __('Request', 'typerocket-ext-dev') . "</th></tr></thead>";
                foreach ($routes->routes as $route) {
                    /** @var \TypeRocket\Http\Route $route */
                    $methods = implode(', ', $route->methods);
                    $vars = implode(', ', $route->match['args']);

                    if($route->addTrailingSlash === false) {
                        $slash = 'no';
                    } else {
                        $slash = 'auto';
                    }

                    echo "<tr><td>{$route->match['regex']}</td><td>{$vars}</td><td class='tr-w-10'>{$slash}</td><td>{$methods}</td></tr>";
                }
                echo '</table>';
            }
            if(!empty($rules)) {
                echo "<p><strong>WordPress Routes</strong>: The current registered rewrite rules.</p>";
                echo '<table class="wp-list-table widefat fixed striped">';
                echo "<thead><tr><th>" . __('Rewrite Rule', 'typerocket-ext-dev') . "</th><th>" . __('Match', 'typerocket-ext-dev') . "</th></tr></thead>";
                foreach ($rules as $rule => $match) {
                    echo "<tr><td>$rule</td><td>$match</td></tr>";
                }
                echo '</table>';
            } else {
                _e("<p>Enable <a href=\"https://codex.wordpress.org/Using_Permalinks\">Pretty Permalinks</a> under <a href=\"/wp-admin/options-permalink.php\">Permalink Settings</a>. \"Pretty Permalinks\" are required for TypeRocket to work.</p>", 'typerocket-ext-dev');
            }
            echo '</div>';
        };

        $tabs = \TypeRocket\Elements\Tabs::new()->layoutLeft();
        $tabs->tab('Icons', 'dashicons-admin-generic', $icons)->setDescription('Search custom icons');
        $tabs->tab('Images', 'dashicons-images-alt', [$this, 'images'])->setDescription('Image & media info');
        $tabs->tab('Routes', 'dashicons-admin-site-alt3', $rules)->setDescription('Rewrite rules & routes');
        $tabs->tab('Migrations', 'dashicons-database', [$this, 'migrations'])->setDescription('Database migrations');
        $tabs->tab('Roles', 'dashicons-groups', [$this, 'roles'])->setDescription('WP user roles');
        $tabs->tab('Capabilities', 'dashicons-admin-network', [$this, 'capabilities'])->setDescription('WP user capabilities');
        $tabs->render();
    }

    public function images()
    {
        function tr_dev_get_all_image_sizes() {
            global $_wp_additional_image_sizes;

            $default_image_sizes = get_intermediate_image_sizes();
            $image_sizes = [];

            foreach ( $default_image_sizes as $size ) {
                $image_sizes[ $size ][ 'wp' ] = 'yes';
                $image_sizes[ $size ][ 'width' ] = intval( get_option( "{$size}_size_w" ) );
                $image_sizes[ $size ][ 'height' ] = intval( get_option( "{$size}_size_h" ) );
                $image_sizes[ $size ][ 'crop' ] = get_option( "{$size}_crop" ) ? get_option( "{$size}_crop" ) : false;
            }

            if ( isset( $_wp_additional_image_sizes ) && count( $_wp_additional_image_sizes ) ) {
                $image_sizes = array_merge( $image_sizes, $_wp_additional_image_sizes );
            }

            return $image_sizes;
        }

        $img_sizes = tr_dev_get_all_image_sizes();
        $url = admin_url('options-media.php');

        echo '<div class="tr-p-20">';
        echo Element::title(__('Images', 'typerocket-ext-dev'), 'dashicons-images-alt');
        echo '<p>'.__('All image sizes added by', 'typerocket-ext-dev').' <a href="https://developer.wordpress.org/reference/functions/add_image_size/">add_image_size()</a>.</p>';
        echo '<p><a target="_blank" class="button button-primary" href="'.$url.'">'.__('Manage media default sizes', 'typerocket-ext-dev').'</a></p>';
        if($img_sizes) {
            echo '<table class="wp-list-table widefat fixed striped">';
            echo "<thead><tr><th>" . __('Name') . "</th><th>" . __('Height') . "</th><th>" . __('Width') . "</th><th>" . __('Crop') . "</th><th>" . __('WordPress') . "</th></tr></thead>";
            foreach ($img_sizes as $name => $d) {
                /** @var \WP_Role $role */
                $wp = $d['wp'] ?? '';

                // see _wp_add_additional_image_sizes()
                if(in_array($name, ['1536x1536', '2048x2048'])) {
                    $wp = 'yes';
                }

                echo "<tr><td>{$name}</td><td>{$d['height']}</td><td>{$d['width']}</td><td>{$d['crop']}</td><td>{$wp}</td></tr>";
            }
            echo '</table>';
        }
        echo '</div>';
    }

    public function roles()
    {
        echo '<div class="tr-p-20">';
        echo Element::title(__('Roles', 'typerocket-ext-dev'), 'dashicons-groups');
        $roles = (new Roles)->all();
        if($roles) {
            echo '<table class="wp-list-table widefat fixed striped">';
            echo "<thead><tr><th>" . __('Name', 'typerocket-ext-dev') . "</th><th>" . __('Label', 'typerocket-ext-dev') . "</th></tr></thead>";
            foreach ($roles as $role) {
                /** @var \WP_Role $role */
                $label = translate_user_role($role['label']);
                echo "<tr><td>{$role['name']}</td><td>{$label}</td></tr>";
            }
            echo '</table>';
        }
        echo '</div>';
    }

    public function capabilities()
    {
        echo '<div class="tr-p-20">';
        echo Element::title(__('Capabilities', 'typerocket-ext-dev'), 'dashicons-admin-network');
        $cap = (new Roles)->capabilities();
        if($cap) {
            ksort($cap);
            echo '<table class="wp-list-table widefat fixed striped">';
            echo "<thead><tr><th>" . __('Name', 'typerocket-ext-dev') . "</th><th>" . __('Roles', 'typerocket-ext-dev') . "</th></tr></thead>";
            foreach ($cap as $name => $roles) {
                /** @var \WP_Role $role */
                $roles = implode('</div><div>', $roles);
                echo "<tr><td>{$name}</td><td><div>{$roles}</div></td></tr>";
            }
            echo '</table>';
        }
        echo '</div>';
    }

    public function migrations()
    {
        $folders = apply_filters('typerocket_dev_migration_folders', [
            'typerocket_migrations' => Config::get('paths.migrations')
        ]);

        echo '<div class="tr-p-20">';
        echo Element::title(__('Migrations', 'typerocket-ext-dev'), 'dashicons-database');

        foreach ($folders as $option_name => $migrations_folder) {
            $migrations = null;
            if(file_exists($migrations_folder)) {
                $migrations = array_diff(scandir($migrations_folder), ['..', '.'] );
                $migrations = array_flip($migrations);
            }

            echo Html::p($option_name);
            $migrations_run = maybe_unserialize(get_option($option_name)) ?: [];
            if($migrations) {
                echo '<table class="wp-list-table widefat fixed striped">';
                echo "<thead><tr><th>" . __('Name', 'typerocket-ext-dev') . "</th><th>" . __('Time Run', 'typerocket-ext-dev') . "</th></tr></thead>";
                foreach ($migrations as $name => $index) {
                    if(!empty($migrations_run[$name])) {
                        $time =  \DateTime::createFromFormat('U.u', $migrations_run[$name])->format('Y-m-d\TH:i:s.u');
                        echo "<tr><td><b>{$name}</b></td><td><b>{$time}</b></td></tr>";
                    } else {
                        echo "<tr><td>{$name}</td><td></td></tr>";
                    }

                }
                echo '</table>';
            } else {
                _e("<p>No migrations found.</p>");
            }

        }
        echo '</div>';
    }

    /**
     * Add Footer Tools
     */
    public function footerTools()
    {
        global $wp_version, $wpdb, $template, $wp_actions, $wp_filter;

        if(defined( 'XMLRPC_REQUEST' ) || defined( 'REST_REQUEST' ) || ( defined( 'WP_INSTALLING' ) && WP_INSTALLING ) || wp_doing_ajax() || wp_is_json_request()) {
            return;
        }

        if(
            !\TypeRocket\Http\Response::getFromContainer()->sends('html') ||
            !Config::env('TYPEROCKET_DEV_TOOLBAR', true) ||
            Config::env('WHOOPS_WP_HTML_ERROR_HANDLED', false)
        ) {
            return;
        }

        $queries = $wpdb->queries ?? [];
        $total = 0;

        $manifest = RuntimeCache::getFromContainer()->get('manifest');
        $url = Config::get('urls.typerocket');
        $num = $wpdb->num_queries;
        $num_class = $num > 10 ? ($num > 40 ? ($num > 70 ? 'warning' : 'bad') : 'weak') : 'good';
        ?>
        <link rel="stylesheet" href="<?php echo $url . $manifest['/css/dev.css']; ?>">
        <div id="tr-dev-toolbar">
            <div class="dev-nav">
                <a tabindex="0" href="#tr-dev-toolbar-details" class="item" id="tr-dev-toolbar-details-js">MySQL Queries:
                    <b class="tr-dev-toolbar-details-num tr-dev-toolbar-details-num-<?php echo $num_class; ?>"><?php echo $num ?></b>
                </a>
                <a tabindex="0" href="#tr-dev-toolbar-hooks" class="item" id="tr-dev-toolbar-hooks-js">Hooks</a>
                <a tabindex="0" href="<?php echo get_site_url(null, '/tr-dev/tools/flush-rules'); ?>" id="tr-dev-toolbar-flush-js" class="item">Flush Rules</a>
                <span class="item">DOMContentLoaded: <b id="tr-dev-toolbar-dct">...</b></span>
                <span class="item">PHP: <b><?php echo PHP_VERSION; ?></b></span>
                <span class="item">WordPress: <b><?php echo $wp_version; ?></b></span>
                <?php if($template) : ?>
                <span class="item">Template: <b><?php echo basename($template); ?></b></span>
                <?php endif; ?>
                <span class="item">Request: <b><?php echo $_SERVER['REQUEST_METHOD'] ?? '?'; ?> &rarr; <?php echo http_response_code() ?: '200'; ?></b></span>
                <?php do_action('typerocket_dev_tools_nav_bar'); ?>
            </div>
            <?php do_action('typerocket_dev_tools_nav_bar_details'); ?>
            <div id="tr-dev-toolbar-details" style="display: none">
                <?php if (defined('SAVEQUERIES')) : ?>
                    <div class="tr-dev-toolbar-details-section tr-input-dark">
                    <table class="tr-dev-toolbar-details-table">
                        <thead>
                            <tr class="tr-dev-toolbar-details-table-head">
                                <th class="tr-dev-toolbar-details-table-head-item-id">#</th>
                                <th class="tr-dev-toolbar-details-table-head-item-sql">SQL</th>
                                <th class="tr-dev-toolbar-details-table-head-item-time">Time</th>
                                <th class="tr-dev-toolbar-details-table-head-item-called">Call Stack</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($queries as $i => $query) : ?>
                            <?php
                            [$sql, $speed, $caller, $time] = $query;
                            $sel = Html::select(['class'=>'tr-dev-toolbar-select-css'])->nest(array_map(function($value) {
                                return Html::option([], $value);
                            }, array_reverse(explode(',', $caller))));
                            $total += $speed;
                            ?>
                            <tr class="tr-dev-toolbar-details-table-row">
                                <td class="tr-dev-toolbar-details-table-item tr-dev-toolbar-details-table-item-id"><?php echo $i + 1; ?></td>
                                <td class="tr-dev-toolbar-details-table-item tr-dev-toolbar-details-table-item-sql"><?php echo esc_html($sql); ?></td>
                                <td class="tr-dev-toolbar-details-table-item tr-dev-toolbar-details-table-item-time"><?php echo substr($speed * 1000 , 0, 9); ?>ms</td>
                                <td class="tr-dev-toolbar-details-table-item tr-dev-toolbar-details-table-item-select"><?php echo $sel; ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    </div>
                    <span class="tr-dev-toolbar-details-table-summary">Total time running MySQL queries: <?php echo $total * 1000; ?>ms</span>
                <?php else : ?>
                    <span class="tr-dev-toolbar-details-table-summary">
                        Add <code>define('SAVEQUERIES', true);</code> to your <i>wp-config.php</i> file to view details information about your MySQL queries here.
                    </span>
                <?php endif; ?>
            </div>
            <div id="tr-dev-toolbar-hooks" style="display: none">
                <div class="tr-dev-toolbar-details-section tr-input-dark">
                <table class="tr-dev-toolbar-details-table">
                    <thead>
                    <tr class="tr-dev-toolbar-details-table-head">
                        <th class="tr-dev-toolbar-details-table-head-item-hook">Hook</th>
                        <th class="tr-dev-toolbar-details-table-head-item-sql">Priority</th>
                        <th class="tr-dev-toolbar-details-table-head-item-time">Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($wp_actions as $action => $count) :
                        /** @var \WP_Hook $hook */
                        $hook = $wp_filter[$action] ?? null;

                        if($hook) {
                            $callbacks = $hook->callbacks;
                        } else {
                            $callbacks = [ '' => ['' => '']];
                        }

                        $i = 0;
                        foreach ($callbacks as $priority => $callback) : $name = key($callback);

                            $callback = $callback[$name];

                            if ( isset($callback['function']) && is_string( $callback['function'] ) && ( false !== strpos( $callback['function'], '::' ) ) ) {
                                $callback['function'] = explode( '::', $callback['function'] );
                            }

                            if ( isset( $callback['class'] ) ) {
                                $callback['function'] = array(
                                    $callback['class'],
                                    $callback['function'],
                                );
                            }

                            try {
                                $file = $line = null;

                                if(isset($callback['function'])) {
                                    if ( is_array( $callback['function'] ) ) {
                                        if ( is_object( $callback['function'][0] ) ) {
                                            $class  = get_class( $callback['function'][0] );
                                        } else {
                                            $class  = $callback['function'][0];
                                        }

                                        $ref = new \ReflectionMethod( $class, $callback['function'][1] );
                                    } elseif ( is_object( $callback['function'] ) ) {
                                        $ref  = new \ReflectionObject( $callback['function'] );
                                    } else {
                                        $ref = new \ReflectionFunction( $callback['function'] );
                                    }

                                    $file = Str::trimStart($ref->getFileName(), TYPEROCKET_PATH);
                                    $file = Str::trimStart($file, ABSPATH);
                                    $line = $ref->getStartLine();
                                    $name = $ref->getName() ?: $name;
                                }
                            } catch ( \ReflectionException $e ) { }
                        ?>
                        <tr class="tr-dev-toolbar-details-table-row">
                            <?php if(!$i++) : ?>
                            <th rowspan="<?php echo count($callbacks); ?>" class="tr-dev-toolbar-details-table-item tr-dev-toolbar-hooks-table-item-name"><?php echo esc_html($action); ?></th>
                            <?php endif; ?>
                            <td class="tr-dev-toolbar-details-table-item tr-dev-toolbar-details-table-item-time"><?php echo esc_html($priority); ?></td>
                            <td class="tr-dev-toolbar-details-table-item tr-dev-toolbar-details-table-item-sql">
                                <div>
                                    <?php echo $name; ?>
                                </div>
                                <?php if($file) : ?>
                                <div>
                                    <code style="font-size: .8em"><?php echo esc_html($file); ?> on line <?php echo (int) $line; ?></code>
                                </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            </div>
        <script>
            (function() {
                let xdebug = document.querySelector('.xdebug-error');
                let whoops = document.querySelector('.Whoops.container');

                if(xdebug && !whoops) {
                    document.body.innerHTML = xdebug.outerHTML;
                }

                document.querySelector('#tr-dev-toolbar-details-js').addEventListener('click', function(e) {
                    e.preventDefault();
                    console.log('details')
                    var sd = document.querySelector('#tr-dev-toolbar-details');
                    var th = document.querySelector('#tr-dev-toolbar-hooks');
                    sd.style.display = sd.style.display === 'block' ? 'none' : 'block'
                    th.style.display = 'none';
                }, false);

                document.querySelector('#tr-dev-toolbar-hooks-js').addEventListener('click', function(e) {
                    e.preventDefault();
                    console.log('hooks')
                    var sd = document.querySelector('#tr-dev-toolbar-details');
                    var th = document.querySelector('#tr-dev-toolbar-hooks');
                    th.style.display = th.style.display === 'block' ? 'none' : 'block'
                    sd.style.display = 'none';
                }, false);

                document.querySelector('#tr-dev-toolbar-flush-js').addEventListener('click', function(e) {
                    if(!confirm('Flush WordPress rewrite rules?')) {
                        e.preventDefault();
                    }
                }, false);

                window.addEventListener('load', function () {
                    document.querySelector('#tr-dev-toolbar-dct')
                        .innerHTML = (window.performance.timing.domContentLoadedEventEnd - window.performance.timing.navigationStart) + 'ms';
                }, false);
            })()
        </script>
        <?php
    }

    public function addCss()
    {
        /** @var RuntimeCache */
        $url = Config::get('urls.typerocket');
        $manifest = RuntimeCache::getFromContainer()->get('manifest');
        wp_enqueue_style( 'typerocket-styles-dev', $url . $manifest['/css/dev.css']);
    }
}