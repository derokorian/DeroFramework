#!/usr/local/bin/php
<?php

/**
 * Single point of entry
 * User: Ryan Pallas
 * Date: 12/6/13
 */
require_once './bootstrap.php';
require_once ROOT . '/vendor/autoload.php';

$Main = class_exists('\App\Core\Main')
    ? '\App\Core\Main'
    : \Dero\Core\Main::class;

$Main::init();
$Main::run();
