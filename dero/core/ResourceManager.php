<?php

namespace Dero\Core;


class ResourceManager {
    private static $scripts = [];
    private static $styles = [];

    public static function AddScript($fileName) {
        if( substr($fileName, -3, 3) != '.js')
            $fileName .= '.js';
        $path = ROOT . '/public/scripts/' . $fileName;
        if( is_readable($path) && !in_array($fileName,self::$scripts) )
            self::$scripts[] = $fileName;
        else
            throw new \OutOfBoundsException('Unable to read script ' . $fileName .'<br>'.$path);
        return '';
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