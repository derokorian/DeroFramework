<?php
/**
 * Main
 * @author Ryan Pallas
 * @package DeroFramework
 * @namespace Dero\Core
 * @since 2013-12-06
 */

namespace Dero\Core;


class Main
{

    public static function init()
    {
        // Load settings
        $files = glob(dirname(__DIR__) . DS . 'Settings/*.php');
        foreach($files as $file)
        {
            if( is_readable($file) )
                require_once $file;
        }

        self::LoadRoute();
    }

    private static function LoadRoute()
    {
        $bRouteFound = false;
        $strURI = trim($_SERVER['REDIRECT_URL'], '/');

        // Load defind routes
        $aRoutes = [];
        $files = glob(ROOT . DS . 'App' . DS . 'Routes' . DS . '*.php');
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

            if( empty($aRoute['args']) )
                $oController->{$aRoute['method']}();
            else
            {
                $args = [];
                foreach($aRoute['args'] as $arg)
                {
                    $args[] = $aRoute['Match'][0][$arg];
                }
                $oController->{$aRoute['method']}($args);
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