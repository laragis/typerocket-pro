<?php
/** @var \App\Elements\Form $form */
echo $form->setFields(
    $form->text('Title'),
    $form->builder('Blocks')
        ->setComponentGroup(TypeRocket\Extensions\PageBuilder::FIELD_NAME)
        ->setName(TypeRocket\Pro\Extensions\PageBuilderPlus\Models\Block::FIELD_NAME)
)->save($button)->useConfirm();

if(!empty($revs)) {
    $this->include('revisions.list', ['revs' => $revs ?? []]);
}