<?php

/**
 * Single point of entry
 * User: Ryan Pallas
 * Date: 12/6/13
 */
require_once './bootstrap.php';
require_once ROOT . '/vendor/autoload.php';

use Dero\Core\Timing;

$Main = class_exists('\App\Core\Main')
    ? '\App\Core\Main'
    : \Dero\Core\Main::class;

$Main::init();

if (IS_DEBUG) {
    ob_start();
    Timing::start('elapsed');
}

$Main::run();

if (IS_DEBUG) {
    Timing::end('elapsed');
    Timing::setHeaderTimings();
    ob_end_flush();
}
