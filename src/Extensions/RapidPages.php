<?php
namespace TypeRocket\Pro\Extensions;

use TypeRocket\Core\Config;
use TypeRocket\Http\Cookie;
use TypeRocket\Http\Redirect;
use TypeRocket\Http\Request;
use TypeRocket\Http\Response;
use TypeRocket\Http\Route;
use TypeRocket\Utility\File;
use TypeRocket\Utility\Str;
use TypeRocket\Pro\Utility\Http;
use TypeRocket\Pro\Utility\Log;
use TypeRocket\Pro\Utility\Storage;

/**
 * You can use this extension to generate static file for
 * your web server to access. The side effects will
 * vary depending on your setup. Or, you can use
 * the advanced-cache.php WP drop-in.
 *
 * php galaxy extension:publish typerocket/professional rapid-pages
 *
 * In Nginx you might have the following when using
 * the default root storage drive:
 *
 * set $new_request_uri $request_uri;
 *
 * if ($request_uri ~ ^\/(.*)\/$) {
 *   set $new_request_uri $1;
 * }
 *
 * if ($request_uri ~ ^\/$) {
 *   set $new_request_uri index;
 * }
 *
 * location / {
 *   try_files /tr_static/rapid_cache/$new_request_uri.html $uri $uri/ /index.php?$query_string;
 * }
 */
class RapidPages
{
    public $template;

    /**
     * RapidPages constructor.
     */
    public function __construct()
    {
        if(!Config::env('TYPEROCKET_RAPID_PAGES', true)) {
            return;
        }

        add_action('admin_bar_menu', [$this, 'menu'], 50, 3);

        // Light cache clearing for common updates
        add_action('save_post', [$this, 'clearPostUrlCache'], 50);
        add_action('wp_update_nav_menu_item', static::class.'::flush', 50);
        add_action('customize_save_after', static::class.'::flush', 50);
        add_action('upgrader_process_complete', static::class.'::flush', 50);

        if(strtolower(Request::new()->getHeader('X-No-Cache')) == 'yes') {
            return;
        }

        add_filter('template_include', [$this, 'template'], 10, 3);
        add_action('typerocket_routes', [$this, 'routes']);
    }

    /**
     * Flush cache of post type when saved.
     *
     * @param $post_id
     */
    public function clearPostUrlCache($post_id)
    {
        if($url = get_permalink( get_post($post_id) ) ) {
            static::remove($url);
        }
    }

    /**
     * Load routes
     */
    public function routes()
    {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        Route::new()->get()->match('tr-rapid-pages/flush-cache')->do(function() {
            Response::getFromContainer()->noRapidPagesCache();
            static::flush();
            Redirect::new()->withFlash('Rapid Pages\' cache flushed.')->back()->now();
        })->name('flush-cache');
    }

    /**
     * @param \WP_Admin_Bar $wp_admin_bar
     */
    public function menu(\WP_Admin_Bar $wp_admin_bar)
    {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $wp_admin_bar->add_menu([
            'id'    => 'typerocket-rapid-pages',
            'parent' => null,
            'group'  => null,
            'title' => '<span style="top: 2px;" class="dashicons dashicons-superhero ab-icon"></span><span class="ab-label">Rapid Pages</span>',
            'href'  => '#',
            'meta' => [
                'title' => 'Rapid Pages',
            ]
        ]);

        $wp_admin_bar->add_menu( [
            'id'     => 'typerocket-rapid-pages-flush',
            'parent' => 'typerocket-rapid-pages',
            'group'  => null,
            'title'  => 'Flush Cache',
            'href'   => Route::buildUrl('flush-cache'),
            'meta'   => [
                'title' => 'Flush Cache',
            ]
        ]);
    }

    /**
     * @param $template
     *
     * @return mixed
     */
    public function template($template)
    {
        // Avoid having a single user generate every cache page
        if(!wp_get_current_user() && Cookie::new()->getOtherwiseSet('TYPEROCKET_RAPID_PAGES_HIT', 'yes', MINUTE_IN_SECONDS * 10)) {
            return $template;
        }

        $this->template = basename($template);
        add_action('shutdown', [$this, 'tryToCache']);

        return $template;
    }

    /**
     * Try To Cache
     */
    public function tryToCache()
    {
        // Just quite if there is an error or redirect
        if(!in_array(http_response_code(), [200])) {
            return;
        }

        global $post_ID;
        $can_cache = Config::env('WP_CACHE', false);
        $ignore = Config::get('rapid.ignore', ['paths' => [], 'templates' => [], 'post_ids']);
        $ignore = apply_filters('typerocket_ext_rapid_pages_ignore', $ignore);
        $request = Request::new();
        $path = trim($request->getPath(), '/');
        $url = $request->getUriFull();

        $templates = $ignore['templates'] ?? [];
        $paths = $ignore['paths'] ?? [];
        $post_ids = $ignore['post_ids'] ?? [];

        if($can_cache && $templates && in_array($this->template, $templates)) {
            $can_cache = false;
        }

        if($can_cache && $post_ids && in_array($post_ID, $post_ids)) {
            $can_cache = false;
        }

        if($can_cache && $paths && Str::pregMatchFindFirst($paths, $path)) {
            $can_cache = false;
        }

        $can_cache = apply_filters('typerocket_ext_rapid_pages_can_cache', $can_cache);
        $can_cache ? static::create($url) : static::remove($url);
    }

    /**
     * @return \TypeRocket\Pro\Utility\Drives\Drive
     */
    public static function getDrive()
    {
        $drive = Config::get('rapid.drive', 'root');
        return Storage::driver($drive);
    }

    /**
     * @param $url
     *
     * @return string
     */
    public static function getHtmlCacheFileFromUrl($url)
    {
        $folder = static::getCacheFolder();
        $location = static::getRelativeCachePathFromUrl($url);
        return $folder . DIRECTORY_SEPARATOR . ltrim($location . '.html', '\\/');
    }

    /**
     * @param string $url
     *
     * @return string
     */
    public static function getRelativeCachePathFromUrl($url)
    {
        $path = trim(Str::replaceFirst(home_url(), '', $url), '/');
        return $path ?: $path . '/index';
    }

    /**
     * @return string
     */
    public static function getCacheFolder()
    {
        $path = Config::get('rapid.folder', 'tr_static');
        return trim($path, '\\/') . '/rapid_cache';
    }

    /**
     * @return string
     */
    public static function getCacheFolderFullPath()
    {
        return static::getDrive()->path(static::getCacheFolder());
    }

    /**
     * @throws \Throwable
     */
    public static function flush()
    {
        $path = static::getDrive()->path(static::getCacheFolder());
        try {
            $bool = File::new($path)->removeRecursiveDirectory();
        } catch (\Exception $e) {
            Log::warning('Rapid Pages: '. $e->getMessage());
        }

        do_action('typerocket_ext_rapid_pages_after_flush', $bool, $path);
    }

    /**
     * @param string $url
     */
    public static function remove($url)
    {
        $bool = static::getDrive()->delete(static::getHtmlCacheFileFromUrl($url));
        do_action('typerocket_ext_rapid_pages_after_remove', $bool, $url);
    }

    /**
     * @param string $url
     */
    public static function replace($url)
    {
        static::remove($url);
        static::create($url);
    }

    /**
     * @param string $url
     */
    public static function create($url)
    {
        $html_file = static::getHtmlCacheFileFromUrl($url);
        $created_cache = false;
        $exists = static::getDrive()->exists($html_file);
        $response = null;

        if(!$exists) {
            $headers = ['X-No-Cache' => ' yes', 'X-Dev-Tools' => 'off', 'X-TypeRocket-Rapid-Pages-Request' => 'yes'];
            $response = Http::get($url)->headers($headers)->exec(false, false);
            $content = $response->code() == '200' ? $response->body() : null;
            $no_cache = $response->header('x-no-cache') == 'yes';
            $content = apply_filters('typerocket_ext_rapid_pages_content', $content, $response);

            if($content && $response->is200() && !$no_cache) {
                $content .= '<!-- TypeRocket Rapid Pages -->';
                $created_cache = static::getDrive()->replace($html_file, $content);
            } else {
                Log::warning("Rapid Pages: Did not cache {$url}. You might want to ignore this URL's path to help with performance.");
            }
        }

        do_action('typerocket_ext_rapid_pages_after_create', $created_cache, $response);
    }
}