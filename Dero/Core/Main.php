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
        $strURI = trim($_SERVER['REQUEST_URI'], '/');
        $aRoutes = [];
        $files = glob(ROOT . DIRECTORY_SEPARATOR . 'App' . DIRECTORY_SEPARATOR . 'Routes' . DIRECTORY_SEPARATOR . '*.php');
        foreach($files as $file)
            include_once $file;
        foreach($aRoutes as $aRoute)
        {
            if( preg_match_all($aRoute, $strURI, $match) )
            {
                $aRoute['Request'] = $strURI;
                $aRoute['Match'] = $match;
                self::LoadRoute($aRoute);
                break;
            }
        }
    }

    private static function LoadRoute(Array $aRoute)
    {
        $con = new $aRoute['controller']();
        if( empty($aRoute['args']) )
            $con->{$aRoute['method']}();
        else
        {
            $args = [];
            foreach($aRoute['args'] as $arg)
                $args[] = $aRoute['Match'][0][$arg];
            $con->{$aRoute['method']}($args);
        }
    }
} 