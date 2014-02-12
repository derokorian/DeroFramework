<?php
/**
 * Main
 * @author Ryan Pallas
 * @package DeroFramework
 * @namespace dero\core
 * @since 2013-12-06
 */

namespace Dero\Core;

class Main
{

    public static function init()
    {
        // Load settings
        $files = glob(dirname(__DIR__) . '/settings/*.php');
        foreach($files as $file)
        {
            if( is_readable($file) && is_file($file) )
                require_once $file;
        }

        self::LoadRoute();
    }

    private static function LoadRoute()
    {
        $bRouteFound = false;
        if( PHP_SAPI === 'cli' )
        {
            $strURI = !empty($GLOBALS["argv"][1]) ? $GLOBALS["argv"][1] : '';
        }
        else
        {
            $strURI = trim($_GET['REQUEST'], '/');
        }

        // Load defined routes
        $aRoutes = [];
        $files = glob(ROOT . '/app/routes/*.php');
        foreach($files as $file)
            include_once $file;

        $fLoadRoute = function(Array $aRoute)
        {
            if( empty($aRoute['dependencies']) )
                $oController = new $aRoute['controller']();
            else
            {
                $aDeps = array();
                foreach( $aRoute['dependencies'] as $strDependency )
                {
                    if( class_exists($strDependency) )
                    {
                        $aDeps[] = new $strDependency();
                    }
                }
                $oRef = new \ReflectionClass($aRoute['controller']);
                $oController = $oRef->newInstanceArgs($aDeps);
            }

            if( is_numeric($aRoute['method']) )
            {
                $method = $aRoute['Match'][$aRoute['method']];
            }
            else
            {
                $method = $aRoute['method'];
            }

            if( empty($aRoute['args']) )
            {
                $oController->{$method}();
            }
            else
            {
                $args = [];
                foreach($aRoute['args'] as $arg)
                {
                    $args[] = $aRoute['Match'][$arg];
                }
                $oController->{$method}($args);
            }
        };

        // Attempt to find the requested route
        foreach($aRoutes as $aRoute)
        {
            if( preg_match($aRoute['pattern'], $strURI, $match) )
            {
                $bRouteFound = true;
                $aRoute['Request'] = $strURI;
                $aRoute['Match'] = $match;
                $fLoadRoute($aRoute);
                break;
            }
        }

        // If route wasn't found, try to load default
        if( !$bRouteFound && isset($aRoutes['default']) )
        {
            $fLoadRoute($aRoutes['default']);
        }
        else
        {
            // ToDo: Need some handling here for undefined request and no default
        }
    }
} 