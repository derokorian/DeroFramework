<?php

namespace Dero\Core;

/**
 * Class TemplateEngine
 *
 * @package Dero\Core
 */
class TemplateEngine
{
    private static $NAMESPACES = ['Dero\Core\\', 'App\Controller\\'];

    /**
     * Loads a view file from configurable template paths/extensions
     *   and returns the contents (if exists) after being parsed as Tpl
     *
     * @param       $strView
     * @param array $vars
     *
     * @return mixed
     */
    public static function LoadView(string $strView, Array $vars = []) : string
    {
        $aExt = Config::getValue('website', 'template', 'extensions');
        $aPath = Config::getValue('website', 'template', 'paths');
        foreach ($aPath as $sPath) {
            foreach ($aExt as $strExt) {
                $strFile = ROOT . DS . $sPath . DS . $strView . '.' . $strExt;
                if (file_exists($strFile) && is_readable($strFile) && is_file($strFile)) {
                    $strContent = file_get_contents($strFile);

                    return self::ParseTemplate($strContent, $vars);
                }
            }
        }
    }

    /**
     * Parses a given template, using the provided array of vars
     *
     * @param       $strContent
     * @param array $vars
     *
     * @return mixed
     */
    public static function ParseTemplate(string $strContent, Array $vars = []) : string
    {
        static::replaceIterations($strContent, $vars);
        static::callMethodArgs($strContent, $vars);
        static::replaceTplAndVars($strContent, $vars);
        static::replaceVarsWithDefault($strContent, $vars);

        return $strContent;
    }

    /**
     * Replaces iterations in a given template
     *
     * @example <ul>
     *              {each|array>entry}
     *                  <li>{entry}</li>
     *              {/each}
     *          </ul>
     *
     * @param       $strContent
     * @param array $vars
     */
    protected static function replaceIterations(string &$strContent, array $vars = [])
    {
        if (preg_match_all('#(?<!\{)\{each\|(\w+)>(\w+)\}(.*?)\{\/each\}#is', $strContent, $matches)) {
            foreach ($matches[1] as $k => $match) {
                $body = '';
                if (isset($vars[$match]) && is_array($vars[$match])) {
                    $RepeatBody = $matches[3][$k];
                    $replaces = $vars[$match];
                    foreach ($replaces as $replace) {
                        $body .= str_replace('{' . $matches[2][$k] . '}', $replace, $RepeatBody);
                    }
                }
                $strContent = str_replace($matches[0][$k], $body, $strContent);
            }
        }
    }

    /**
     * Calls static methods or global with the static keyword
     *   with arguments, which maybe in the given args
     *
     * @example {static>date(Y)}
     *          {ResourceManager>AddStyle(libraries/bbcodes)}
     *          {ResourceManager>LoadStyles()}
     *
     * @param       $strContent
     * @param array $vars
     */
    protected static function callMethodArgs(string &$strContent, array $vars = [])
    {
        if (preg_match_all('#(?<!\{)\{(\w+)>(\w+)\((.*)\)\}#i', $strContent, $matches)) {
            foreach ($matches[0] as $k => $match) {
                $class = $matches[1][$k];
                $method = $matches[2][$k];
                $args = $matches[3][$k];
                foreach (self::$NAMESPACES as $ns) {
                    if (class_exists($ns . $class)) {
                        $class = $ns . $class;
                    }
                }
                if (class_exists($class) || $class == 'static') {
                    if (method_exists($class, $method) || ($class == 'static' && function_exists($method))) {
                        if (preg_match('/(?<![^\\\]\\\)' . preg_quote(',', '/') . '/', $args)) {
                            $args = preg_split('/(?<![^\\\]\\\)' . preg_quote(',', '/') . '/', $args);;
                            foreach ($args as &$arg) {
                                if (isset($vars[$arg])) {
                                    $arg = $vars[$arg];
                                }
                                unset($arg);
                            }
                            $strReplace = call_user_func_array($class . '::' . $method, $args);
                        }
                        elseif (strlen($args) > 0) {
                            if (isset($vars[$args])) {
                                $strReplace = call_user_func($class . '::' . $method, $vars[$args]);
                            }
                            else {
                                $strReplace = call_user_func($class . '::' . $method, $args);
                            }
                        }
                        else {
                            $strReplace = call_user_func($class . '::' . $method);
                        }
                        $strContent = str_replace($match, $strReplace, $strContent);
                    }
                    else {
                        throw new \UnexpectedValueException('Method not found (' . $class . '::' . $method . ')');
                    }
                }
                else {
                    throw new \UnexpectedValueException('Class not found (' . $class . ')');
                }
                $strContent = str_replace($match, '', $strContent);
            }
        }
    }

    /**
     * Replaces inline templates, any direct access of givens vars, constants or super globals
     *
     * @example {tpl|user/login_header} Loading an addition template inline
     *          {_POST|username} IE saving form values on submit when not complete
     *          {PHP_INT_MAX}  Global constants work as well
     *          {title} an offset in the given $vars
     *
     * @param       $strContent
     * @param array $vars
     */
    protected static function replaceTplAndVars(string &$strContent, array $vars = [])
    {
        if (preg_match_all('#(?<!\{)\{(\w+)(\|([\w\\\/]+))?\}#i', $strContent, $matches)) {
            foreach ($matches[1] as $k => $match) {
                switch ($match) {
                    case 'tpl':
                        $strContent = str_replace($matches[0][$k],
                                                  self::LoadView($matches[3][$k]),
                                                  $strContent);
                        break;
                    case '_SERVER':
                        $strContent = str_replace($matches[0][$k],
                                                  isset($_SERVER[$matches[3][$k]]) ? $_SERVER[$matches[3][$k]] : '',
                                                  $strContent);
                        break;
                    case '_POST':
                        $strContent = str_replace($matches[0][$k],
                                                  isset($_POST[$matches[3][$k]]) ? $_POST[$matches[3][$k]] : '',
                                                  $strContent);
                        break;
                    case '_GET':
                        $strContent = str_replace($matches[0][$k],
                                                  isset($_GET[$matches[3][$k]]) ? $_GET[$matches[3][$k]] : '',
                                                  $strContent);
                        break;
                    case defined($match):
                        $strContent = str_replace($matches[0][$k],
                                                  constant($match),
                                                  $strContent);
                        break;
                    case isset($vars[$match]):
                        $strContent = str_replace($matches[0][$k],
                                                  $vars[$match],
                                                  $strContent);
                        break;
                    default:
                        foreach (self::$NAMESPACES as $ns) {
                            $class = $ns . $match;
                            if (class_exists($class)) {
                                $action = $matches[3][$k];
                                $class = new $class();
                                if (method_exists($class, $action)) {
                                    $strContent = str_replace($matches[0][$k], call_user_func_array('self::View', $class->$action()), $strContent);
                                }
                                elseif (property_exists($class, $action)) {
                                    $strContent = str_replace($matches[0][$k], $class->$action, $strContent);
                                }
                            }
                        }
                }
            }
        }
    }

    /**
     * Replaces vars in the template with the given var, or a default if not found
     *
     * @example {username|Enter username}
     *          {amount_ordered|0}
     *
     * @param       $strContent
     * @param array $vars
     */
    protected static function replaceVarsWithDefault(string &$strContent, array $vars = [])
    {
        if (preg_match_all('#(?<!\{)\{(\w+)\|(.+)\}#i', $strContent, $matches)) {
            foreach ($matches[1] as $k => $match) {
                if (isset($vars[$match])) {
                    $replace = $vars[$match];
                }
                else {
                    $replace = $matches[2][$k];
                }
                $strContent = str_replace($matches[0][$k], $replace, $strContent);
            }
        }
    }

    /**
     * Used for calling global functions within a template
     *
     * @param $func
     * @param $args
     *
     * @return bool|mixed
     */
    public static function __callStatic(string $func, array $args)
    {
        if (function_exists($func)) {
            return call_user_func_array($func, $args);
        }

        return false;
    }
}
