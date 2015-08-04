#!/usr/local/bin/php
<?php

/**
 * Single point of entry
 * User: Ryan Pallas
 * Date: 12/6/13
 */
define('ROOT', dirname(__DIR__));
define('DS', DIRECTORY_SEPARATOR);

require_once __DIR__ . '/autoload.php';

Dero\Core\Timing::start('elapsed');
Dero\Core\Main::run();
Dero\Core\Timing::end('elapsed');

echo "\n";
Dero\Core\Timing::printTimings();
echo "\n";