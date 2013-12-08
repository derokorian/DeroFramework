<?php

/**
 * Routes for blog pages
 */
$routes['default'] = [
    'pattern' => '/^$/',
    'controller' => 'App\Controller\BlogController',
    'dependencies' => 'App\Model\BlogModel',
    'method' => 'index',
    'args' => []
];
$routes[] = [
    'pattern' => '#^blog/view/([0-9]+/)?$#i',
    'controller' => 'App\Controller\BlogController',
    'dependencies' => 'App\Model\BlogModel',
    'method' => 'viewPost',
    'args' => [1]
];