<?php

include 'Timer.php';
$t = getTimer();

/**
 * Single point of entry
 * User: Ryan Pallas
 * Date: 12/6/13
 */
define('ROOT', dirname(__DIR__));
define('DS', DIRECTORY_SEPARATOR);

/*
 * Define error reporting settings
 */
if( (bool)getenv('PHP_DEBUG')  === true )
{
    ini_set('error_reporting', E_ALL);
    ini_set('display_errors', true);
    ini_set('log_errors', false);
}
else
{
    ini_set('error_reporting', E_WARNING);
    ini_set('log_errors', true);
    ini_set('display_errors', false);
    ini_set('error_log', dirname(__DIR__) . '/logs/' . date('Y-m-d') . '-error.log');
}

spl_autoload_register(function ($strClass)
{
    $strFile = $strClass . '.php';
    $strNameSpace = '';
    if ( ($iLast = strripos($strClass, '\\')) !== FALSE ) {
        $strNameSpace = DS . str_replace('\\',DS,substr($strClass, 0, $iLast));
        $strNameSpace = implode('_', preg_split('/(?<=[a-zA-Z])(?=[A-Z])/s', $strNameSpace));
        $strFile = substr($strClass, $iLast + 1) . '.php';
    }
    $strFilePath = ROOT . strtolower($strNameSpace) . DS . $strFile;
    if( is_readable($strFilePath) ) {
        require_once $strFilePath;
        return TRUE;
    }
    return FALSE;
});

function dump(&$var)
{
    echo "<pre>";
    var_dump($var);
    echo "</pre>";
    exit;
}

Dero\Core\Main::Init();

header('x-timing-elapsed: '. $t->getElapsed());