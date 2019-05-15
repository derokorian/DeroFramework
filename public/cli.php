#!/usr/local/bin/php
<?php

/**
 * Single point of entry
 * User: Ryan Pallas
 * Date: 12/6/13
 */
require_once './bootstrap.php';

$Main = \Dero\Core\Main::getMainClass();
$Main::init();
$Main::run();
