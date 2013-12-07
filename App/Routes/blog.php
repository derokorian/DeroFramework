<?php

/**
 * Routes for blog pages
 */
$routes['default'] = [
    'pattern' => '/^$/',
    'controller' => 'BlogController',
    'method' => 'index',
    'args' => []
];
$routes[] = [
    'pattern' => '#^blog/view/([0-9]+/)?$#i',
    'controller' => 'BlogController',
    'method' => 'viewPost',
    'args' => ['$1']
];