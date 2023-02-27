<?php
/**
 * This template is used when a template route is found
 *
 * @var $this \TypeRocket\Pro\Http\RouteTemplateCollection
 */
if(!defined('TYPEROCKET_ROUTE_TEMPLATE')) {
    define('TYPEROCKET_ROUTE_TEMPLATE', $this->found);
}