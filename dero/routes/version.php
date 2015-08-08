<?php

/**
 * routes for install pages
 */
$aRoutes[] = [
    'pattern'      => '#^install$#i',
    'controller'   => 'Dero\Controller\VersionController',
    'dependencies' => [],
    'method'       => 'upgrade',
    'args'         => []
];
$aRoutes[] = [
    'pattern'      => '#^upgrade$#i',
    'controller'   => 'Dero\Controller\VersionController',
    'dependencies' => [],
    'method'       => 'upgrade',
    'args'         => []
];
