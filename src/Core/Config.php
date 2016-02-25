<?php

namespace Dero\Core;

/**
 * Configuration retriever
 *
 * @author Ryan Pallas
 */
class Config
{
    const PATHS = [
        ROOT . '/vendor/derokorian/deroframework/src/' . 'config' . DS,
        ROOT . '/src/' . 'config' . DS,
        ROOT . DS . 'app' . DS . 'config' . DS,
        ROOT . DS . 'config' . DS,
    ];
    private static $Config = [];

    /**
     * Gets a configuration value
     *
     * @param $args array[string] The name(s) of the configuration parameter to get
     *
     * @example config::GetValue('database','default','engine')
     * @return mixed value of the configuration or null if not found
     */
    public static function GetValue(...$args)
    {
        if (count($args) > 0) {
            self::LoadConfig($args[0]);
            $last = self::$Config;
            foreach ($args as $arg) {
                if (isset($last[$arg])) {
                    $last = $last[$arg];
                }
                else {
                    return null;
                }
            }

            return $last;
        }

        return null;
    }

    /**
     * Loads the configuration if not already initialized
     *
     * @param $sFile
     */
    private static function LoadConfig($sFile)
    {
        if (!array_key_exists($sFile, self::$Config)) {
            $aConfig = [];
            $sFilename = $sFile . '.json';
            foreach (static::PATHS as $sPath) {
                if (file_exists($sPath . $sFilename) && is_readable($sPath . $sFilename)) {
                    $aConfig = self::MergeConfig(
                        $aConfig,
                        jsonc_decode(
                            file_get_contents($sPath . $sFilename),
                            true
                        )
                    );
                }
            }
            self::$Config[$sFile] = $aConfig;
        }
    }

    /**
     * @param mixed $aConfig
     * @param mixed $aVal
     *
     * @returns array
     */
    private static function MergeConfig(Array $aConfig, Array $aVal)
    {
        $aReturn = [];

        foreach ($aVal as $k => $v) {
            if (isset($aConfig[$k]) && is_array($aConfig[$k]) && is_array($v)) {
                $aReturn[$k] = self::MergeConfig($aConfig[$k], $v);
            }
            elseif (is_numeric($k)) {
                $aReturn[] = $v;
            }
            else {
                $aReturn[$k] = $v;
            }
        }
        foreach ($aConfig as $k => $v) {
            if (is_numeric($k) && !in_array($v, $aReturn)) {
                $aReturn[] = $v;
            }
            elseif (!isset($aReturn[$k])) {
                $aReturn[$k] = $v;
            }
        }

        return $aReturn;
    }
}

/**
 * Decodes JSON with extended support for comments
 *
 * @param      $strJson
 * @param bool $bAssoc
 * @param int  $iDepth
 * @param int  $iOptions
 *
 * @return mixed
 */
function jsonc_decode($strJson, $bAssoc = false, $iDepth = 512, $iOptions = 0)
{
    $strJson = preg_replace('@/\*.*?\*/@m', null, $strJson);
    $strJson = preg_replace('@^\s*(//|#).*$@m', null, $strJson);

    return json_decode($strJson, $bAssoc, $iDepth, $iOptions);
}

