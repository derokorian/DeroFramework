<?php

/**
 * routes for install pages
 */
$aRoutes[] = [
    'pattern' => '#^install$#i',
    'controller' => 'App\Controller\InstallController',
    'dependencies' => [],
    'method' => 'index',
    'args' => []
];