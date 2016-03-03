<?php
/**
 * Main
 *
 * @author    Ryan Pallas
 * @package   DeroFramework
 * @namespace dero\core
 * @since     2013-12-06
 */

namespace Dero\Core;

use Dero\Controller\BaseController;

class Main
{
    public static function getMainClass()
    {
        return class_exists('\App\Core\Main')
            ? '\App\Core\Main'
            : static::class;
    }

    /**
     * Initializes and runs the app for the current request
     */
    public static function run()
    {
        $aRoute = static::findRoute();
        if (!empty($aRoute)) {
            static::loadRoute($aRoute);
        }
    }

    /**
     * Loads the controllers and models necessary to complete the given route request
     *
     * @param string $sRouteURI
     *
     * @return array
     */
    public static function findRoute(string $sRouteURI = null) : array
    {
        $strURI = $sRouteURI ?? static::getRouteURI();
        $aRoutes = static::getDefinedRoutes();

        // Attempt to find the requested route
        foreach ($aRoutes as $aRoute) {
            if (!empty($aRoute['pattern'])
                && preg_match($aRoute['pattern'], $strURI, $match)
            ) {
                $aRoute['Request'] = $strURI;
                $aRoute['Match'] = $match;
                if (!class_exists($aRoute['controller']) ||
                    !method_exists(
                        $aRoute['controller'],
                        is_numeric($aRoute['method'])
                            ? $aRoute['Match'][$aRoute['method']]
                            : $aRoute['method']
                    )
                ) {
                    // Class or method does not exist,  use default
                    break;
                }

                return $aRoute;
            }
        }

        // If route wasn't found, try to load default
        if (isset($aRoutes['default'])) {
            return $aRoutes['default'];
        }

        return [];
    }

    /**
     * Gets the requested route path based on the request type
     *
     * @return string
     */
    protected static function getRouteURI() : string
    {
        if (PHP_SAPI === 'cli') {
            define('IS_API_REQUEST', false);

            return !empty($GLOBALS["argv"][1]) ? $GLOBALS["argv"][1] : '';
        }
        else {
            $strURI = trim($_GET['REQUEST'], '/');
            if (substr($strURI, 0, 3) == 'api') {
                define('IS_API_REQUEST', true);

                return substr($strURI, 4);
            }
            else {
                define('IS_API_REQUEST', false);

                return $strURI;
            }
        }
    }

    /**
     * Gets the applications defined routes
     *
     * @return array
     */
    protected static function getDefinedRoutes() : array
    {
        static $aRoutes = [];

        if (empty($aRoutes)) {
            foreach (Config::getValue('application', 'paths', 'routes') as $path) {
                foreach (glob(ROOT . DS . $path) as $file) {
                    is_readable($file) && include_once $file;
                }
            }
        }

        return $aRoutes;
    }

    /**
     * Loads a given route
     *
     * @param array $aRoute
     */
    public static function loadRoute(array $aRoute)
    {
        $oController = static::getController($aRoute);
        $sMethod = static::getMethod($aRoute);
        $aArgs = static::getArgs($aRoute);

        Timing::start('controller');
        $mRet = $oController->{$sMethod}(...$aArgs);
        Timing::end('controller');

        static::handleResult($mRet);
    }

    /**
     * Gets a controller object from the given route definition
     *
     * @param array $aRoute
     *
     * @return BaseController
     */
    protected static function getController(array $aRoute) : BaseController
    {
        if (empty($aRoute['dependencies'])) {
            return new $aRoute['controller']();
        }
        else {
            $aDeps = [];
            foreach ($aRoute['dependencies'] as $strDependency) {
                if (class_exists($strDependency)) {
                    $aDeps[] = new $strDependency();
                }
            }

            return new $aRoute['controller'](...$aDeps);
        }
    }

    /**
     * Gets the method to call from the given route definition
     *
     * @param array $aRoute
     *
     * @return string
     */
    protected static function getMethod(array $aRoute) : string
    {
        if (is_numeric($aRoute['method'])) {
            return $aRoute['Match'][$aRoute['method']];
        }
        else {
            return $aRoute['method'];
        }
    }

    /**
     * Gets the arguments for the controller method from the given route definition
     *
     * @param array $aRoute
     *
     * @return array
     */
    protected static function getArgs(array $aRoute) : array
    {
        $aArgs = [];
        if (isset($aRoute['args']) && is_array($aRoute['args'])) {
            foreach ($aRoute['args'] as $arg) {
                if (isset($aRoute['Match'][$arg])) {
                    $aArgs[] = $aRoute['Match'][$arg];
                }
                elseif (!is_numeric($arg)) {
                    $aArgs[] = $arg;
                }
            }
        }

        return $aArgs;
    }

    /**
     * Outputs the result of the controller
     *
     * @param $mRet
     */
    protected static function handleResult($mRet)
    {
        if (is_scalar($mRet)) {
            echo $mRet;
        }
        elseif (!is_null($mRet)) {
            $mRet = json_encode($mRet);
            if (PHP_SAPI !== 'cli') {
                header('Content-Type: application/json');
                header('Content-Length: ' . strlen($mRet));
            }
            echo $mRet;
        }
    }

    /**
     * Initializes and runs the application
     */
    public static function init()
    {
        static::initSettings();
        static::initErrors();
        static::initSession();

        if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST' &&
            isset($_SERVER['CONTENT_TYPE']) && stripos($_SERVER['CONTENT_TYPE'], 'application/json') === 0
        ) {
            $_POST = json_decode(file_get_contents('php://input'), true);
        }
    }

    /**
     * Loads application settings, usually files that define constants
     */
    protected static function initSettings()
    {
        $files = glob(dirname(__DIR__) . '/settings/*.php');
        foreach ($files as $file) {
            if (is_readable($file) && is_file($file)) {
                require_once $file;
            }
        }
    }

    /**
     * Sets the application error settings
     */
    protected static function initErrors()
    {
        if (IS_DEBUG) {
            ini_set('error_reporting', E_ALL);
            ini_set('display_errors', true);
            ini_set('log_errors', false);
        }
        else {
            ini_set('error_reporting', E_WARNING);
            ini_set('display_errors', false);
            ini_set('log_errors', true);
            ini_set('error_log', ROOT . '/logs/' . date('Y-m-d') . '-error.log');
        }
    }

    /**
     * Initializes a session for this request
     */
    protected static function initSession()
    {
        session_name(Config::getValue('security', 'sessions', 'name'));
        session_set_cookie_params(
            Config::getValue('security', 'sessions', 'lifetime'),
            '/',
            parse_url(Config::getValue('website', 'site_url'), PHP_URL_HOST),
            false,
            true
        );
        session_start();
    }
}
