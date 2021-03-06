<?php

namespace Dero\Core;

use OutOfBoundsException;

/**
 * Class ResourceManager
 *
 * @package   DeroFramework
 * @namespace Dero\Core
 * @since     2014-02-09
 */
class ResourceManager
{
    private static $scripts = [];
    private static $styles = [];

    private static $KNOWN_SCRIPTS = [];

    private static $KNOWN_STYLES = [];

    /**
     * Add a javascript file as a necessary resource
     *
     * @param $filename
     */
    public static function addScript(string $filename)
    {
        // Load scripts from configuration
        if (count(static::$KNOWN_SCRIPTS) == 0) {
            static::$KNOWN_SCRIPTS = Config::getValue('resources', 'scripts') ?? [];
        }

        self::addResource(
            $filename,
            '.js',
            ROOT . '/public/scripts/',
            Config::getValue('website', 'script_url') ?? '',
            static::$KNOWN_SCRIPTS,
            self::$scripts
        );
    }

    private static function addResource(
        string $filename,
        string $ext,
        string $path,
        string $url,
        array $known,
        array &$target
    )
    {
        // Check if requesting a configured resource
        if (($k = array_search($filename, array_column($known, 'name'))) !== false) {
            // Check if we already added it
            if (in_array(self::genSrc($known[$k]['src'], $url), $target)) {
                return;
            }
            // Add any defined dependencies
            if (count($known[$k]['dep']) > 0) {
                foreach ($known[$k]['dep'] as $dep) {
                    static::addResource($dep, $ext, $path, $url, $known, $target);
                }
            }
            $target[] = self::genSrc($known[$k]['src'], $url);
        }
        else {
            // if no extension then add it
            $l = strlen($ext);
            if (substr($filename, -$l, $l) != $ext) {
                $filename .= $ext;
            }

            // define a path where the file should exist, and check if its there
            $path = $path . $filename;
            if (is_readable($path) && !in_array(self::genSrc($filename, $url), $target)) {
                $target[] = self::genSrc($filename, $url);
            }
            else {
                throw new OutOfBoundsException('Unable to read resource ' . $filename);
            }
        }
    }

    private static function genSrc(string $strSrc, string $strBaseUrl): string
    {
        if (substr($strSrc, 0, 2) == '//') {
            return $strSrc;
        }

        return $strBaseUrl . $strSrc;
    }

    /**
     * Add a css file as a necessary resource
     *
     * @param $filename
     */
    public static function addStyle(string $filename)
    {
        if (count(static::$KNOWN_STYLES) == 0) {
            static::$KNOWN_STYLES = Config::getValue('resources', 'styles') ?? [];
        }

        self::addResource(
            $filename,
            '.css',
            ROOT . '/public/styles/',
            Config::getValue('website', 'style_url') ?? '',
            self::$KNOWN_STYLES,
            self::$styles
        );
    }

    /**
     * Loads the requested javascript files
     *
     * @return mixed
     */
    public static function loadScripts(): string
    {
        return TemplateEngine::LoadView('scripts', ['scripts' => self::$scripts]);
    }

    /**
     * Loads the requested css files
     *
     * @return mixed
     */
    public static function loadStyles(): string
    {
        return TemplateEngine::LoadView('styles', ['styles' => static::$styles]);
    }
}
