<?php
namespace TypeRocket\Pro\Features;

use Whoops\Handler\JsonResponseHandler;
use Whoops\Handler\PlainTextHandler;
use Whoops\Run;
use Whoops\Util\Misc;

class Whoops
{
    public function __construct()
    {
        define('TYPEROCKET_WHOOPS', true);
        $run = new Run;

        if( !empty($_POST['_tr_ajax_request']) || !empty($_GET['_tr_ajax_request']) || Misc::isAjaxRequest() ) {
            $run->prependHandler(new JsonResponseHandler);
        }
        elseif ( defined('TYPEROCKET_GALAXY') || Misc::isCommandLine() ) {
            $run->prependHandler( new PlainTextHandler );
        } else {
            $run->prependHandler(new WhoopsHtmlHandler);
        }

        $run->register();
    }
}