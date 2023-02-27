<?php
namespace TypeRocket\Pro\Extensions\PageBuilderPlus;

use TypeRocket\Core\Config;
use TypeRocket\Elements\Fields\Builder;
use TypeRocket\Extensions\PageBuilder;
use TypeRocket\Http\Request;
use TypeRocket\Register\MetaBox;
use TypeRocket\Utility\Helper;
use TypeRocket\Register\Page;
use TypeRocket\Http\Route;
use TypeRocket\Pro\Extensions\PageBuilderPlus\Models\Block;
use TypeRocket\Pro\Extensions\PageBuilderPlus\Models\BuilderRevision;
use TypeRocket\Pro\Template\TachyonTemplateEngine;
use TypeRocket\Template\View;

class PageBuilderPlus
{
    public const MIGRATIONS_KEY = 'typerocket_page_builder_plus';
    public const MIGRATIONS_FOLDER = 'database/ext/page_builder_plus';

    public function __construct()
    {
        $page_settings = array_filter([
            'capability' => apply_filters('typerocket_page_builder_plus_capability', null)
        ]);

        $conf = Config::get('app');
        $pro_path = Config::get('paths.pro');
        Page::addResourcePages('Block', 'Blocks', $page_settings, 'block', BlockController::class)->setIcon('dashicons-screenoptions');

        $page_rev = tr_page('builder_revisions', 'edit', 'Builder Revisions', $page_settings);
        $page_rev->setHandler(BuilderRevisionsController::class);
        $page_rev->addPage($page_rev);
        $page_rev->mapAction('POST', 'restore');

        add_filter('typerocket_component_name', function($name, $type, $component) {
            if($type === Builder::TEMPLATE_TYPE) {
                $title = __("Create a block from this component.", 'typerocket-core');
                $name .= "<div class=\"tr-save-component-as-block-wrap\"><button class=\"tr-save-component-as-block\" aria-label=\"{$title}\" title=\"{$title}\"><i class=\"dashicons dashicons-insert\"></i></button></div>";
            }
            return $name;
        }, 10, 3);

        add_filter('typerocket_dev_migration_folders', function ($folders) use ($pro_path) {
            $folders[static::MIGRATIONS_KEY] = rtrim($pro_path, '/\\') . '/' . static::MIGRATIONS_FOLDER;
            return $folders;
        });

        $oldComponents = null;

        add_filter('typerocket_controller_on_validate_save', function($valid, $controller, $args) use (&$oldComponents) {
            $class = Helper::controllerClass('Page');

            if($controller instanceof $class) {
                $type = $args[0];
                $model = $args[1];
                $field = PageBuilder::FIELD_NAME;
                $oldComponents = $model->meta->{$field} ?? null;
            }

            return $valid;
        }, 10, 3);

        add_filter('typerocket_controller_on_action_save', function($controller, $args) use (&$oldComponents) {
            $class = Helper::controllerClass('Page');

            if($controller instanceof $class) {
                $type = $args[0];
                $model = $args[1];

                if($type === 'update') {
                    $new_data = Request::new()->getFields(PageBuilder::FIELD_NAME);
                    BuilderRevision::maybeSaveRevision($model, $oldComponents, $new_data);
                }
            }
        }, 10, 2);

        add_filter('typerocket_search_field_result', function($title, $value, $options, $error) {
            if($options['registered'] !== Block::class && !$options['registered'] instanceof Block) {
                return $title;
            }

            if($error) {
                return $title;
            }

            $id = (int) ( is_array($value) ? $value['id'] : $value );
            $block = (new Block)->find($id);

            if(!$block) {
                return $title;
            }

            $url = $block->getSearchUrl();
            $text = __('Edit block in new tab', 'typerocket-core');

            return $title . " <div class=\"tr-block-component-actions tr-search-results-hide\"><a target=\"_blank\" href=\"{$url}\">{$text}</a> <span class=\"dashicons dashicons-external\"></span></div>";
        }, 10, 4);

        MetaBox::new('Builder Revisions')->addScreen('page')->gutenbergOff()->setCallback(function() use ($page_rev) {
            global $post;
            $page = Helper::modelClass('Page');
            $page = $page->wpPost($post, true);
            $revs = $page->builder_revisions()->with(['user'])->orderBy('id', 'DESC')->get();

            echo static::view('revisions.list', compact('revs'));
        })->addToRegistry();

        if (
            !in_array('\TypeRocket\Extensions\PageBuilder', $conf['extensions']) &&
            !in_array('TypeRocket\Extensions\PageBuilder', $conf['extensions'])
        ) {
              \TypeRocket\Elements\Notice::permanent([
                'type' => 'error',
                'message' => __('This extension requires the Page Builder extension.', 'typerocket-core'),
            ]);
        }

        add_action('admin_footer', function() { ?>
            <script type="text/javascript">
                window.TypeRocket.pageBuilderPlus = true;
            </script>
            <?php
        });

        add_action('typerocket_routes', [$this, 'routes']);
    }

    public static function routes()
    {
        Route::new()->post()
            ->match('tr-api/block')
            ->do([BlockController::class, 'create']);
    }

    public static function view($name, $data)
    {
        return View::new($name, $data)->setEngine(TachyonTemplateEngine::class)->setFolder(__DIR__ . '/../views/pagebuilderplus');
    }
}
