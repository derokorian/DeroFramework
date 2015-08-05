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
    public static function run()
    {
        static::init();
        $aRoute = static::findRoute();
        if (!empty($aRoute)) {
            static::loadRoute($aRoute);
        }
    }

    /**
     * Initializes and runs the application
     * @codeCoverageIgnore
     */
    public static function init()
    {
        /*
         * Define error reporting settings
         */
        define('IS_DEBUG', !empty(getenv('PHP_DEBUG')) || !empty($_GET['debug']));
        if( IS_DEBUG )
        {
            ini_set('error_reporting', E_ALL);
            ini_set('display_errors', true);
            ini_set('log_errors', false);
        }
        else
        {
            ini_set('error_reporting', E_WARNING);
            ini_set('display_errors', false);
            ini_set('log_errors', true);
            ini_set('error_log', dirname(__DIR__) . '/logs/' . date('Y-m-d') . '-error.log');
        }

        if( isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST' &&
            isset($_SERVER['CONTENT_TYPE']) && stripos($_SERVER['CONTENT_TYPE'], 'application/json') === 0)
        {
            $_POST = json_decode(file_get_contents('php://input'), true);
        }

        // Load settings
        static::loadSettings();

        $strSessionName = Config::GetValue('security','sessions', 'name');
        session_name($strSessionName);
        session_set_cookie_params(
            Config::GetValue('security', 'sessions', 'lifetime'),
            '/',
            parse_url(Config::GetValue('website', 'site_url'), PHP_URL_HOST),
            false,
            true
        );
        session_start();
    }

    protected static function loadSettings()
    {
        $files = glob(dirname(__DIR__) . '/settings/*.php');
        foreach($files as $file)
        {
            if( is_readable($file) && is_file($file) )
                require_once $file;
        }
    }

    /**
     * Loads the controllers and models necessary to complete the given route request
     * @codeCoverageIgnore
     */
    private static function findRoute()
    {
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
        $files = glob(ROOT . '/dero/routes/*.php');
        foreach($files as $file)
        {
            is_readable($file) && include_once $file;
        }
        $files = glob(ROOT . '/app/routes/*.php');
        foreach($files as $file)
        {
            is_readable($file) && include_once $file;
        }

        // Attempt to find the requested route
        foreach($aRoutes as $aRoute)
        {
            if( !empty($aRoute['pattern'])
                && preg_match($aRoute['pattern'], $strURI, $match) )
            {
                $aRoute['Request'] = $strURI;
                $aRoute['Match'] = $match;
                if (!class_exists($aRoute['controller']) ||
                    !method_exists(
                        $aRoute['controller'],
                        is_numeric($aRoute['method'])
                            ? $aRoute['Match'][$aRoute['method']]
                            : $aRoute['method']
                        ))
                {
                    // Class or method does not exist,  use default
                    break;
                }
                return $aRoute;
            }
        }

        // If route wasn't found, try to load default
        if (isset($aRoutes['default']))
        {
            return $aRoutes['default'];
        }
        return [];
    }

    protected static function loadRoute(array $aRoute)
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
            $oController = new $aRoute['controller'](...$aDeps);
        }

        if( is_numeric($aRoute['method']) )
        {
            $method = $aRoute['Match'][$aRoute['method']];
        }
        else
        {
            $method = $aRoute['method'];
        }

        Timing::start('controller');
        if( empty($aRoute['args']) || !isset($aRoute['Match'][$aRoute['args'][0]]))
        {
            $mRet = $oController->{$method}();
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
                $mRet = $oController->{$method}(...$args);
            }
            else
            {
                $mRet = $oController->{$method}($aRoute['Match'][$aRoute['args'][0]]);
            }
        }
        Timing::end('controller');

        if( is_scalar($mRet) )
        {
            echo $mRet;
        }
        elseif( !is_null($mRet) )
        {
            $mRet = json_encode($mRet);
            header('Content-Type: application/json');
            header('Content-Length: '. strlen($mRet));
            echo $mRet;
        }
    }
} 