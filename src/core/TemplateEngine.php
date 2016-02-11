<?php

/**
 * Created by PhpStorm.
 * User: Ryan
 * Date: 12/20/13
 * Time: 7:33 PM
 */

namespace Dero\Core;


class TemplateEngine
{
    private static $NAMESPACES = ['Dero\Core\\', 'App\Controller\\'];

    public static function __callStatic($func, $args)
    {
        if (function_exists($func)) {
            return call_user_func_array($func, $args);
        }

        return false;
    }

    private static function replaceTplAndVars($sContent, array $vars = [])
    {
        // {case|arg} IE: {tpl|scripts} {_SERVER|HTTP_HOST}
        // {var} variable or constant name IE: {strUsername} {PHP_INT_MAX}
        if (preg_match_all('#(?<!\{)\{(\w+)(\|([\w\\\/]+))?\}#i', $sContent, $matches)) {
            foreach ($matches[1] as $k => $match) {
                switch ($match) {
                    case 'tpl':
                        $sContent = str_replace($matches[0][$k],
                                                self::LoadView($matches[3][$k]),
                                                $sContent);
                        break;
                    case '_SERVER':
                        $sContent = str_replace($matches[0][$k],
                                                isset($_SERVER[$matches[3][$k]]) ? $_SERVER[$matches[3][$k]] : '',
                                                $sContent);
                        break;
                    case '_POST':
                        $sContent = str_replace($matches[0][$k],
                                                isset($_POST[$matches[3][$k]]) ? $_POST[$matches[3][$k]] : '',
                                                $sContent);
                        break;
                    case '_GET':
                        $sContent = str_replace($matches[0][$k],
                                                isset($_GET[$matches[3][$k]]) ? $_GET[$matches[3][$k]] : '',
                                                $sContent);
                        break;
                    case defined($match):
                        $sContent = str_replace($matches[0][$k],
                                                constant($match),
                                                $sContent);
                        break;
                    case isset($vars[$match]):
                        $sContent = str_replace($matches[0][$k],
                                                $vars[$match],
                                                $sContent);
                        break;
                    default:
                        try {
                            foreach (self::$NAMESPACES as $ns) {
                                $class = $ns . $match;
                                if (class_exists($class)) {
                                    $action = $matches[3][$k];
                                    $class = new $class();
                                    if (method_exists($class, $action)) {
                                        $sContent = str_replace($matches[0][$k], $class->$action(), $sContent);
                                    }
                                    elseif (property_exists($class, $action)) {
                                        $sContent = str_replace($matches[0][$k], $class->$action, $sContent);
                                    }
                                }
                            }
                        } catch (\Exception $e) {
                            $sContent = str_replace($matches[0][$k], '', $sContent);
                        }
                }
            }
        }

        // {someVariableName|defaultValue}
        if (preg_match_all('#(?<!\{)\{(\w+)\|(.+)\}#i', $sContent, $matches)) {
            foreach ($matches[1] as $k => $match) {
                if (isset($$match)) {
                    $replace = $$match;
                }
                else {
                    $replace = $matches[2][$k];
                }
                $sContent = str_replace($matches[0][$k], $replace, $sContent);
            }
        }
    }

    public static function LoadView($strView, Array $vars = [])
    {
        $aExt = Config::GetValue('website', 'template', 'extensions');
        $aPath = Config::GetValue('website', 'template', 'paths');
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

    public static function ParseTemplate($sContent, array $vars = [])
    {
        static::replaceIterations($sContent, $vars);
        static::replaceMethodsWithArgs($sContent, $vars);
        static::replaceTplAndVars($sContent, $vars);

        return $sContent;
    }

    private static function replaceIterations(&$sContent, array $vars = [])
    {
        if (preg_match_all('#(?<!\{)\{each\|(\w+)>(\w+)\}(.*?)\{\/each\}#is', $sContent, $matches)) {
            foreach ($matches[1] as $k => $match) {
                $body = '';
                if (isset($vars[$match]) && is_array($vars[$match])) {
                    $RepeatBody = $matches[3][$k];
                    foreach ($vars[$match] as $replace) {
                        $body .= str_replace('{' . $matches[2][$k] . '}', $replace, $RepeatBody);
                    }
                }
                $sContent = str_replace($matches[0][$k], $body, $sContent);
            }
        }
    }

    private static function replaceMethodsWithArgs($sContent, array $vars = [])
    {
        if (preg_match_all('#(?<!\{)\{(\w+)::(\w+)\((.*)\)\}#i', $sContent, $matches)) {
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
                                if (isset($var[$arg])) {
                                    $arg = $var[$arg];
                                }
                                unset($arg);
                            }
                            $strReplace = call_user_func_array($class . '::' . $method, $args);
                        }
                        elseif (strlen($args) > 0) {
                            if (isset($var[$args])) {
                                $strReplace = call_user_func($class . '::' . $method, $var[$args]);
                            }
                            else {
                                $strReplace = call_user_func($class . '::' . $method, $args);
                            }
                        }
                        else {
                            $strReplace = call_user_func($class . '::' . $method);
                        }
                        $sContent = str_replace($match, $strReplace, $sContent);
                    }
                    else {
                        throw new \UnexpectedValueException('Method not found (' . $class . '::' . $method . ')');
                    }
                }
                else {
                    throw new \UnexpectedValueException('Class not found (' . $class . ')');
                }
                $sContent = str_replace($match, '', $sContent);
            }
        }
    }
}
