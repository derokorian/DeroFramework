<?php

/**
 * routes for blog pages
 */
$aRoutes[] = [
    'pattern' => '/^(blog)?$/',
    'controller' => 'App\Controller\BlogController',
    'dependencies' => ['App\Model\BlogModel'],
    'method' => 'index',
    'args' => []
];
$aRoutes[] = [
    'pattern' => '#^blog/view/([0-9]+)/?$#i',
    'controller' => 'App\Controller\BlogController',
    'dependencies' => ['App\Model\BlogModel'],
    'method' => 'viewPost',
    'args' => [1]
];
$aRoutes[] = [
    'pattern' => '#^blog/edit/([0-9]+)/?$#i',
    'controller' => 'App\Controller\BlogController',
    'dependencies' => ['App\Model\BlogModel'],
    'method' => 'editPost',
    'args' => [1]
];
$aRoutes[] = [
    'pattern' => '#^blog/add/?$#i',
    'controller' => 'App\Controller\BlogController',
    'dependencies' => ['App\Model\BlogModel'],
    'method' => 'addPost',
    'args' => [2]
];
$aRoutes[] = [
    'pattern' => '#^blog/delete/?$#i',
    'controller' => 'App\Controller\BlogController',
    'dependencies' => ['App\Model\BlogModel'],
    'method' => 'deletePost',
    'args' => [1]
];
