<?php

/**
 * Created by PhpStorm.
 * User: Ryan
 * Date: 12/20/13
 * Time: 7:33 PM
 */

namespace Dero\Core;


class TemplateEngine {
    private static $NAMESPACES = [];

    public static function LoadView($strFile, Array $vars = [])
    {
        $strContent = file_get_contents($strFile);
        self::ParseTemplate($strContent, $vars);
    }

    public static function ParseTemplate($strContent, Array $vars = [])
    {
        extract($vars);

        // replace embedded templates, variables, and constants
        if (preg_match_all('#\{(\w+)(\|([\w\\\]+))?\}#i', $strContent, $matches)) {
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
                    case isset($$match):
                        $strContent = str_replace($matches[0][$k],
                            $$match,
                            $strContent);
                        break;
                    default:
                        foreach( self::$NAMESPACES as $ns ) {
                            $class = $ns . $match;
                            if (class_exists($class)) {
                                $action = $matches[3][$k];
                                $class = new $class();
                                if (method_exists($class, $action)) {
                                    $strContent = str_replace($matches[0][$k], call_user_func_array('self::View', $class->$action()), $strContent);
                                } elseif (property_exists($class, $action)) {
                                    $strContent = str_replace($matches[0][$k], $class->$action, $strContent);
                                }
                            }
                        }
                }
                if( strpos($strContent, $matches[0][$k]) !== FALSE )
                    $strContent = str_replace($matches[0][$k], '', $strContent);
            }
        }

        // replace iterations
        if (preg_match_all('#\{each\|(\w+)>(\w+)\}(.*?)\{\/each\}#is', $strContent, $matches)) {
            foreach ($matches[1] as $k => $match) {
                $body = '';
                if( isset($$match) && is_array($$match) ) {
                    $RepeatBody = $matches[3][$k];
                    $replaces = $$match;
                    foreach( $replaces as $replace ) {
                        $body .= str_replace('{'.$matches[2][$k].'}', $replace, $RepeatBody);
                    }
                }
                $strContent = str_replace($matches[0][$k], $body, $strContent);
            }
        }

        // call static methods with arguments
        if( preg_match_all('#\{(\w+)>(\w+)\((.*)\)\}#i', $strContent, $matches) ) {
            foreach ($matches[0] as $k => $match) {
                $class = $matches[1][$k];
                $method = $matches[2][$k];
                $args = $matches[3][$k];
                foreach( self::$NAMESPACES as $ns ) {
                    if( class_exists($ns . $class) ) {
                        $class = $ns.$class;
                    }
                }
                if( class_exists($class) ) {
                    if( method_exists($class, $method) ) {
                        if( strpos($args,',') !== FALSE ) {
                            $args = explode(',', $args);
                            call_user_func_array($class .'::'. $method, $args);
                        } elseif( strlen($args) > 0 ) {
                            call_user_func($class .'::'. $method, $args);
                        } else {
                            call_user_func($class .'::'. $method);
                        }
                    } else {
                        throw new \UnexpectedValueException('Method not found ('.$class.'::'.$method.')');
                    }
                } else {
                    throw new \UnexpectedValueException('Class not found ('.$class.')');
                }
                $strContent = str_replace($match, '', $strContent);
            }
        }

        $strContent = preg_replace('#\{[a-z0-9|]+\}#i', '', $strContent);
        return $strContent;
    }
} 