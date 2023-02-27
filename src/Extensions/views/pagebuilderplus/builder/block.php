<?php
/**
 * @var $first_item
 * @var $item_id
 * @var $model
 * @var $data
 * @var $nested
 */
$block = ($data['model'])->findById((int) $data['block']);
if($block->blocks ?? null) {

    $info = [];
    $nested = $nested ?? null;

    $nested = !$first_item || $nested;
    $info = apply_filters('typerocket_page_builder_plus_block_visual_info', $info, compact('first_item','item_id', 'model', 'nested', 'data'));

    \TypeRocket\Elements\Fields\Matrix::componentsLoop($block->blocks, compact('item_id', 'model', 'info', 'nested'), 'builder');
}