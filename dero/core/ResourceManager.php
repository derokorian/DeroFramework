<?php

namespace Dero\Core;


class ResourceManager {
    private static $scripts = [];
    private static $styles = [];

    private static $KNOWN_SCRIPTS = [
        [
            'name' => 'jquery',
            'src' => 'jquery-1.11.0.min.js',
            'dep' => []
        ],
        [
            'name' => 'angular',
            'src' => 'angular.min.js',
            'dep' => ['jquery']
        ]
    ];

    public static function AddScript($fileName) {
        if( in_array($fileName, array_column(static::$KNOWN_SCRIPTS, 'name')) )
        {
            foreach( static::$KNOWN_SCRIPTS as $script )
            {
                if( $script['name'] == $fileName )
                {
                    if( count($script['dep']) > 0 )
                    {
                        foreach( $script['dep'] as $dep )
                        {
                            static::AddScript($dep);
                        }
                    }
                    static::$scripts[] = $script['src'];
                    break;
                }
            }
        }
        else
        {
            if( substr($fileName, -3, 3) != '.js')
            {
                $fileName .= '.js';
            }
            $path = ROOT . '/public/scripts/' . $fileName;
            if( is_readable($path) && !in_array($fileName,self::$scripts) )
            {
                self::$scripts[] = $fileName;
            }
            else
            {
                throw new \OutOfBoundsException('Unable to read script ' . $fileName .'<br>'.$path);
            }
        }
    }

    public static function AddStyle($fileName) {
        if( substr($fileName, -4, 4) != '.css' )
            $fileName .= '.css';
        $path = ROOT . '/public/styles/' . $fileName;
        if( is_readable($path) && !in_array($fileName, self::$styles) )
            self::$styles[] = $fileName;
        else
            throw new \OutOfBoundsException('Unable to read style ' . $fileName);
        return '';
    }

    public static function LoadScripts() {
        return TemplateEngine::LoadView('styles',['styles' => self::$styles]);
    }

    public static function LoadStyles() {
        return TemplateEngine::LoadView('scripts',['scripts' => self::$scripts]);
    }
} 