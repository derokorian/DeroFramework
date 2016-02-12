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
$Main = class_exists('\App\Core\Main')
    ? '\App\Core\Main'
    : \Dero\Core\Main::class;

$Main::init();
$Main::run();
