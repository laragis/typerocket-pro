<?php
/**
 * @var \TypeRocket\Pro\Extensions\PageBuilderPlus\Models\BuilderRevision $revision
 * @var string $link
 * @var string $current
 * @var string $old
 */
echo \TypeRocket\Html\Html::a('Back to item', $link);
echo \TypeRocket\Html\Html::p([], 'From <strong>' . $revision->created_at . '</strong> by ' . $revision->user->display_name . ' on ' . $revision->model . ':'. $revision->post_id);

echo wp_text_diff($current, $old, [
    'title_left' => 'Current',
    'title_right' => 'Old',
]);

echo tr_form([], 'create')->save('Restore Old Version')->useConfirm();