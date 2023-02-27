<?php
namespace TypeRocket\Pro\Extensions\PageBuilderPlus;

use TypeRocket\Extensions\PageBuilder;
use TypeRocket\Register\Registry;
use TypeRocket\Pro\Extensions\PageBuilderPlus\PageBuilderPlus;
use TypeRocket\Pro\Extensions\PageBuilderPlus\Models\Block;
use TypeRocket\Pro\Extensions\PageBuilderPlus\Models\BuilderRevision;
use App\Models\Page;
use TypeRocket\Controllers\Controller;
use TypeRocket\Http\Request;
use TypeRocket\Http\Response;

class BuilderRevisionsController extends Controller
{
    public function edit(BuilderRevision $revision)
    {
        $current = json_encode($revision->getComponents());
        $old = json_encode($revision->components);
        $link = $revision->getParentUrl();

        return PageBuilderPlus::view('revisions.builder', compact('current', 'old', 'revision','link'));
    }

    public function restore(BuilderRevision $revision, Response $response, Request $request)
    {
        $new = BuilderRevision::newRevision($revision->post_id, $revision->getComponents(), $revision->model);
        $message =  __('Builder revision failed to restore!', 'typerocket-core');
        $type = 'error';
        $restored = false;

        $edit_url = $request->getReferer();
        $pageClass = \TypeRocket\Utility\Helper::modelClass('Page', false);
        $postTypeModels = apply_filters('typerocket_ext_builder_post_types', [$pageClass]);

        if($new) {
            $revision_model = ltrim($revision->model, ' \\');

            $postTypeModels = array_map(function($v) {
                return ltrim($v, ' \\');
            }, $postTypeModels);

            if(in_array($revision_model, $postTypeModels)) {
                $restored = update_post_meta($revision->post_id, PageBuilder::FIELD_NAME, $revision->components);
                $edit_url = get_edit_post_link($revision->post_id, 'raw');
            }

            if(in_array($revision_model, [ltrim(Block::class, ' \\')])) {
                $block = Block::new()->find($revision->post_id);
                $block->blocks = $revision->components;
                $restored = (bool) $block->save();

                $edit_url = $block->getSearchUrl();
            }

            $restored = apply_filters('typerocket_page_builder_plus_revision_restore', $restored, $revision, $new);

            if(is_bool($restored) && $restored) {
                $message = __('Builder revision restored!', 'typerocket-core');
                $type = 'success';
            }
        }

        return tr_redirect()->withFlash($message, $type)->toUrl($edit_url);
    }
}
