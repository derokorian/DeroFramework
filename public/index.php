<?php
/**
 * Single point of entry
 * User: Ryan Pallas
 * Date: 12/6/13
 */
define('ROOT', dirname(__DIR__));
define('DS', DIRECTORY_SEPARATOR);

spl_autoload_register(function ($strClass)
{
    $strFile = $strClass . '.php';
    $strNameSpace = '';
    if ( ($iLast = strripos($strClass, '\\')) !== FALSE ) {
        $strNameSpace = DS . str_replace('\\',DS,substr($strClass, 0, $iLast));
        $strFile = substr($strClass, $iLast + 1) . '.php';
    }
    $strFilePath = ROOT . $strNameSpace . DS . $strFile;
    if( is_readable($strFilePath) ) {
        require_once $strFilePath;
        return TRUE;
    }
    return FALSE;
});

Dero\Core\Main::Init();