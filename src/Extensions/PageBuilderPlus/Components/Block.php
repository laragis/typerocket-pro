<?php
namespace TypeRocket\Pro\Extensions\PageBuilderPlus\Components;

use TypeRocket\Utility\Url;
use TypeRocket\Pro\Extensions\PageBuilderPlus\PageBuilderPlus;
use TypeRocket\Pro\Template\AdvancedComponent;

class Block extends AdvancedComponent
{
    protected $title = 'Block';

    /**
     * Admin Fields
     */
    public function fields()
    {
        $form = $this->form();
        $url = admin_url( "admin.php?page=block_add" );

        $content = [
            $form->search('Block')->setModelOptions( \TypeRocket\Pro\Extensions\PageBuilderPlus\Models\Block::class ),
            $form->textContent('<a target="_blank" href="'.$url.'" class="button">Add New Block</a>'),
        ];

        $tabs = tr_tabs();
        $content = apply_filters('typerocket_page_builder_plus_block_component_content', $content, $form, $tabs, $this);
        $tabs->tab('Content', 'dashicons-admin-post', $content);

        do_action('typerocket_page_builder_plus_block_component_tabs', $tabs, $form, $this);

        $tabs->render();
    }

    /**
     * Render
     *
     * @var array $data component fields
     * @var array $info name, item_id, model, first_item, last_item, component_id, hash
     */
    public function render(array $data, array $info)
    {
        $data['model'] = new \TypeRocket\Pro\Extensions\PageBuilderPlus\Models\Block();
        $data = array_merge(compact('data'), $info);
        $data = apply_filters('typerocket_page_builder_plus_block_component_render_data', $data);

        PageBuilderPlus::view('builder.block', $data)->render();
    }
}