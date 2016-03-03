<?php

define('ROOT', dirname(dirname(dirname(__DIR__))));
define('DS', DIRECTORY_SEPARATOR);

require ROOT . '/vendor/autoload.php';

$files = glob(ROOT . '/src/settings/*.php');
foreach ($files as $file) {
    if (is_readable($file) && is_file($file)) {
        require_once $file;
    }
}