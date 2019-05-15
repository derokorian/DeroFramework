<?php

define('ROOT', dirname(__DIR__));
define('DS', DIRECTORY_SEPARATOR);

// Load standard framework stuff and app
spl_autoload_register(function ($strClass) {
    $strFile = $strClass . '.php';
    $strNameSpace = '';

    if (($iLast = strripos($strClass, '\\')) !== false) {
        $strNameSpace = DS . str_replace('\\', DS, substr($strClass, 0, $iLast));
        $strNameSpace = implode('_', preg_split('/(?<=[a-zA-Z])(?=[A-Z])/s', $strNameSpace));
        $strFile = substr($strClass, $iLast + 1) . '.php';
    }

    $strFilePath = ROOT . strtolower($strNameSpace) . DS . $strFile;
    if (is_readable($strFilePath)) {
        require_once $strFilePath;

        if (class_exists($strClass)) {
            return true;
        }
    }

    return false;
});

require_once ROOT . '/vendor/autoload.php';