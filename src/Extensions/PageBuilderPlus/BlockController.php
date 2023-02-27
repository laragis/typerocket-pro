<?php
namespace TypeRocket\Pro\Extensions\PageBuilderPlus;

use \TypeRocket\Database\Query;
use TypeRocket\Extensions\PageBuilder;
use \TypeRocket\Pro\Extensions\PageBuilderPlus\PageBuilderPlus;
use \TypeRocket\Pro\Extensions\PageBuilderPlus\Models\Block;
use \TypeRocket\Pro\Extensions\PageBuilderPlus\Models\BuilderRevision;
use \TypeRocket\Controllers\Controller;
use TypeRocket\Http\Request;
use TypeRocket\Http\Response;

class BlockController extends Controller
{
    /**
     * The index page for admin
     *
     * @return mixed
     */
    public function index()
    {
        $blockModel = (new Block())->with(['lastUser', 'user']);
        return PageBuilderPlus::view('blocks.index', ['blockModel' => $blockModel]);
    }

    /**
     * The add page for admin
     *
     * @return mixed
     */
    public function add()
    {
        $button = 'Add';
        $blockModel = new Block();
        $form = tr_form('block', 'create', null, $blockModel);
        return PageBuilderPlus::view('blocks.form', compact('form', 'button'));
    }

    /**
     * Create item
     *
     * AJAX requests and normal requests can be made to this action
     *
     * @param Response $response
     * @param Request $request
     *
     * @return mixed
     * @throws \Exception
     */
    public function create(Response $response, Request $request)
    {
        $return = $response;

        try {
            $title = null;
            $is_save_component = $request->fields('_save_component_as_block');

            if($is_save_component) {
                $blocks = $request->getFields(PageBuilder::FIELD_NAME) ?? $request->getFields(Block::FIELD_NAME);

                if(empty($blocks) || !is_array($blocks)) {
                    $message = __('Block not created. Nothing to save.');
                    return $response->setMessage($message, 'error');
                } else {
                    if($main = reset($blocks)) {
                        if($component = reset($main)) {
                            $component_no_name = __('NA - Enabled Advanced Components');
                            $title = $component['_tr_component_name'] ?? $component_no_name;
                        }
                    }
                }

                $response->setRedirect(false);
            } else {
                $title = $request->getFields('title');
                $return = tr_redirect()->toPage('block', 'index');
                $blocks = $request->getFields('blocks');
            }

            $block = new Block;
            $block->title = $title;
            $block->user_id = get_current_user_id();
            $block->last_user_id = $block->user_id;
            $block->blocks = $blocks;
            $block->created_at = Query::new()->getDateTime();
            $block->updated_at = $block->created_at;
            $block = $block->saveAndGet();
            $url = $block->getSearchUrl();
            $response->flashNext(__(sprintf("Block \"<a target='_blank' href=\"%s\">%s</a>\" created!", $url, $title)));
        } catch (\Exception $e) {
            $response->setMessage($e->getMessage());
            $response->abort(500);
        }

        $response->setData('_tr', ['flashSettings' => [
            'escapeHtml' => false,
            'delay' => 7000,
        ]]);

        return $return;
    }

    /**
     * The edit page for admin
     *
     * @param Block $block
     *
     * @return mixed
     */
    public function edit(Block $block)
    {
        $button = 'Update';
        $form = tr_form($block);
        $revs = $block->builder_revisions()->with(['user'])->orderBy('id', 'desc')->get() ?? [];
        return PageBuilderPlus::view('blocks.form', compact('form', 'button', 'revs'));
    }

    /**
     * Update item
     *
     * AJAX requests and normal requests can be made to this action
     *
     * @param Block $block
     * @param Response $response
     * @param Request $request
     *
     * @return mixed
     */
    public function update(Block $block, Response $response, Request $request)
    {
        BuilderRevision::maybeSaveRevision($block, $block->blocks, $request->getFields('blocks'));

        $block->title = $request->getFields('title');
        $block->blocks = $request->getFields('blocks');
        $block->updated_at = Query::new()->getDateTime();
        $block->last_user_id = get_current_user_id();
        $block->save();
        $response->flashNext('Block updated!');
        return tr_redirect()->toPage('block', 'edit', $block->getID());
    }

    /**
     * Destroy item
     *
     * AJAX requests and normal requests can be made to this action
     *
     * @param Block $block
     * @param Response $response
     *
     * @return mixed
     */
    public function destroy(Block $block, Response $response)
    {
        $block->delete();
        $response->flashNext('Block deleted!', 'warning');
        return tr_redirect()->toPage('block', 'index');
    }
}