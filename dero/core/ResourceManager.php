<?php

namespace Dero\Core;

/**
 * Class ResourceManager
 * @package DeroFramework
 * @namespace Dero\Core
 * @since 2014-02-09
 */
class ResourceManager {
    private static $scripts = [];
    private static $styles = [];

    private static $KNOWN_SCRIPTS = [
// EXAMPLE
//        [
//            'name' => 'jquery',
//            'src' => 'jquery-1.11.0.min.js',
//            'dep' => []
//        ],
//        [
//            'name' => 'angular',
//            'src' => '//ajax.googleapis.com/ajax/libs/angularjs/1.2.14/angular.min.js',
//            'dep' => ['jquery']
//        ]
    ];

    private static $KNOWN_STYLES = [
// EXAMPLE
//        [
//            'name' => 'site',
//            'src' => 'site.css',
//            'dep' => []
//        ],
//        [
//            'name' => 'nav',
//            'src' => 'nav.css',
//            'dep' => ['site']
//        ]
    ];

    private static function genSrc($strSrc) {
        if( substr($strSrc, 0, 2) == '//' ) {
            return $strSrc;
        }
        return Config::GetValue('website', 'script_url') . $strSrc;
    }

    private static function addResource($filename, $ext, $path, $known, &$target)
    {
        // Check if requesting a configured resource
        if( ($k = array_search($filename, array_column($known, 'name'))) !== false )
        {
            // Check if we already added it
            if( in_array(self::genSrc($known[$k]['src']), $target) )
            {
                return;
            }
            // Add any defined dependencies
            if( count($known[$k]['dep']) > 0 )
            {
                foreach( $known[$k]['dep'] as $dep )
                {
                    static::addResource($dep, $ext, $path, $known, $target);
                }
            }
            $target[] = self::genSrc($known[$k]['src']);
        }
        else
        {
            // if no extension then add it
            $l = strlen($ext);
            if( substr($filename, -$l, $l) != $ext)
            {
                $filename .= $ext;
            }

            // define a path where the file should exist, and check if its there
            $path = $path . $filename;
            if( is_readable($path) && !in_array(self::genSrc($filename), $target) )
            {
                $target[] = self::genSrc($filename);
            }
            else
            {
                throw new \OutOfBoundsException('Unable to read resource ' . $filename);
            }
        }
    }

    /**
     * Add a javascript file as a necessary resource
     * @param $filename
     */
    public static function AddScript($filename) {
        // Load scripts from configuration
        if(count(static::$KNOWN_SCRIPTS) == 0)
        {
            static::$KNOWN_SCRIPTS = Config::GetValue('resources','scripts');
        }

        self::addResource($filename, '.js', ROOT.'/public/scripts/', static::$KNOWN_SCRIPTS, self::$scripts);
    }

    /**
     * Add a css file as a necessary resource
     * @param $filename
     */
    public static function AddStyle($filename) {
        if(count(static::$KNOWN_STYLES) == 0)
        {
            static::$KNOWN_STYLES = Config::GetValue('resources','styles');
        }

        self::addResource($filename, '.css', ROOT.'/public/styles/', self::$KNOWN_STYLES, self::$styles);
    }

    /**
     * Loads the requested javascript files
     * @return mixed
     */
    public static function LoadScripts() {
        return TemplateEngine::LoadView('scripts',['scripts' => self::$scripts]);
    }

    /**
     * Loads the requested css files
     * @return mixed
     */
    public static function LoadStyles() {
        return TemplateEngine::LoadView('styles',['styles' => static::$styles]);
    }
} 