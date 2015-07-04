<?php

/**
 * Single point of entry
 * User: Ryan Pallas
 * Date: 12/6/13
 */
define('ROOT', dirname(__DIR__));
define('DS', DIRECTORY_SEPARATOR);

require_once __DIR__ . '/autoload.php';

use Dero\Core\Timing;

ob_start();

Timing::start('elapsed');
Dero\Core\Main::Init();
Timing::end('elapsed');

IS_DEBUG && Timing::setHeaderTimings();

ob_end_flush();
