<?php

// Load standard framework stuff and app
spl_autoload_register(function ($strClass)
{
    $strFile = $strClass . '.php';
    $strNameSpace = '';
    if ( ($iLast = strripos($strClass, '\\')) !== false ) {
        $strNameSpace = DS . str_replace('\\',DS,substr($strClass, 0, $iLast));
        $strNameSpace = implode('_', preg_split('/(?<=[a-zA-Z])(?=[A-Z])/s', $strNameSpace));
        $strFile = substr($strClass, $iLast + 1) . '.php';
    }
    $strFilePath = ROOT . strtolower($strNameSpace) . DS . $strFile;
    if( is_readable($strFilePath) ) {
        require_once $strFilePath;
        return true;
    }
    return false;
});

// load libraries
spl_autoload_register(function ($strClass)
{
    $aExts = ['.php','.inc'];
    $aFilenames = [
        ROOT . '/libraries/class.' . $strClass,
        ROOT . '/libraries/class.' . strtolower($strClass),
        ROOT . '/libraries/' . $strClass,
        ROOT . '/libraries/' . strtolower($strClass),
        ROOT . '/libraries/' . $strClass . '.class',
        ROOT . '/libraries/' . strtolower($strClass) . '.class'
    ];
    foreach($aFilenames as $strFile){
        foreach($aExts as $strExt){
            if(file_exists($strFile.$strExt)&&is_readable(($strFile.$strExt))){
                require_once $strFile.$strExt;
                if(class_exists($strClass))
                    return true;
            }
        }
    }
    return false;
});