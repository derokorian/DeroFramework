<?php

/**
 * routes for blog pages
 */
$aRoutes['default'] = [
    'pattern' => '/^$/',
    'controller' => 'App\Controller\BlogController',
    'dependencies' => ['App\Model\BlogModel'],
    'method' => 'index',
    'args' => []
];
$aRoutes[] = [
    'pattern' => '#^blog/view/([0-9]+/)?$#i',
    'controller' => 'App\Controller\BlogController',
    'dependencies' => ['App\Model\BlogModel'],
    'method' => 'viewPost',
    'args' => [1]
];