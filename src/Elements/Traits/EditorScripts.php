<?php
namespace TypeRocket\Pro\Elements\Traits;

use TypeRocket\Core\Config;

class EditorScripts
{
    static $editorScriptsLoaded = false;

    /**
     * Load Editor Scripts
     *
     * @return bool
     */
    public static function enqueueEditorScripts()
    {
        if(static::$editorScriptsLoaded) {
            return false;
        }

        $url = Config::get('urls.typerocket');
        $edit = Config::get('editor');
        $lang = $edit['lang'] ?? 'en';
        $redactor_file = $edit['load'] == 'minified' ? 'redactor.min.js' : 'redactor.js';
        $redactor_version = '3.4.3';

        $footer = function() use ($edit, $lang) {
            $plugins = json_encode($edit['plugins'] ?? []);
            ?>
            <script type="text/javascript">
                window.TypeRocket.redactor.plugins = <?php echo $plugins; ?>;
                window.TypeRocket.redactor.lang = '<?php echo $lang; ?>';
            </script>
            <?php
        };

        add_action('wp_footer', $footer);
        add_action('admin_footer', $footer);

        wp_enqueue_media();
        wp_enqueue_script( 'typerocket-editor', $url . '/js/lib/redactor/' . $redactor_file, [], $redactor_version, true );

        if(!empty($edit['plugins'])) {
            foreach ($edit['plugins'] as $name) {
                if($name !== 'wpmedia') {
                    wp_enqueue_script( 'typerocket-editor-' . $name, $url . '/js/lib/redactor/_plugins/'.$name.'/'.$name.'.js', ['typerocket-editor'], $redactor_version, true );
                }
            }
        }

        if($lang != 'en') {
            wp_enqueue_script( 'typerocket-editor-lang', $url . '/js/lib/redactor/_langs/'.$lang.'.js', ['typerocket-editor'], $redactor_version, true );
        }

        self::$editorScriptsLoaded = true;

        return true;
    }
}