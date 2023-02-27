<?php
namespace MyNamespace;

class View extends \TypeRocket\Template\View
{
    public function init()
    {
        $this->setFolder(TYPEROCKET_PLUGIN___KEY___VIEWS_PATH);
    }
}