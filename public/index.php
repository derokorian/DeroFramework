<?php

/**
 * Single point of entry
 * User: Ryan Pallas
 * Date: 12/6/13
 */
require_once './bootstrap.php';

use Dero\Core\Main;
use Dero\Core\Timing;


$Main = Main::getMainClass();
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
