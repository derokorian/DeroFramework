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
            define('IS_API_REQUEST', false);
        }
        else
        {
            $strURI = trim($_GET['REQUEST'], '/');
            if( substr($strURI, 0, 3) == 'api' )
            {
                define('IS_API_REQUEST', true);
                $strURI = substr($strURI, 4);
            }
            else
            {
                define('IS_API_REQUEST', false);
            }
        }

        // Load defined routes
        $aRoutes = [];
        $files = glob(ROOT . '/app/routes/*.php');
        foreach($files as $file)
        {
            include_once $file;
        }
        $files = glob(ROOT . '/dero/routes/*.php');
        foreach($files as $file)
        {
            include_once $file;
        }

        $fLoadRoute = function(Array $aRoute)
        {
            if( empty($aRoute['dependencies']) )
                $oController = new $aRoute['controller']();
            else
            {
                $aDep = array();
                foreach( $aRoute['dependencies'] as $strDependency )
                {
                    if( class_exists($strDependency) )
                    {
                        $aDep[] = new $strDependency();
                    }
                }
                $oRef = new \ReflectionClass($aRoute['controller']);
                $oController = $oRef->newInstanceArgs($aDep);
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
                if( count($aRoute['args']) > 1 )
                {
                    $args = [];
                    foreach($aRoute['args'] as $arg)
                    {
                        if( isset($aRoute['Match'][$arg]) )
                        {
                            $args[] = $aRoute['Match'][$arg];
                        }
                    }
                    call_user_func_array([$oController, $method], $args);
                }
                else
                {
                    $oController->{$method}($aRoute['Match'][$aRoute['args'][0]]);
                }
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