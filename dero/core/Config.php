<?php

namespace Dero\Core;

/**
 * Configuration retriever
 * @author Ryan Pallas
 */
class Config
{
    private static $Config = [];

    /**
     * Loads the configuration if not already initialized
     */
    private static function LoadConfig($file)
    {
        if( !array_key_exists($file, self::$Config) ) {
            $config = [];
            if( is_readable(ROOT . DS . 'dero' . DS . 'config' . DS . $file . '.json') )
            {
                $config = self::MergeConfig($config, json_decode(
                    file_get_contents(ROOT . DS . 'dero' . DS . 'config' . DS . $file . '.json'),
                    true
                ));
            }
            if( is_readable(ROOT . DS . 'config' . DS . $file . '.json') )
            {
                $config = self::MergeConfig($config, json_decode(
                    file_get_contents(ROOT . DS . 'config' . DS . $file . '.json'),
                    true
                ));
            }
            self::$Config[$file] = $config;
        }
    }

    /**
     * @param mixed $mConfig
     * @param mixed $mVal
     * @returns array
     */
    private static function MergeConfig(Array $mConfig, Array $mVal)
    {
        $aReturn = [];
        foreach( $mVal as $k => $v )
        {
            if( isset($mConfig[$k]) && is_array($mConfig[$k]) && is_array($v) )
            {
                $aReturn[$k] = self::MergeConfig($mConfig[$k], $v);
            }
            else
            {
                $aReturn[$k] = $v;
            }
        }
        foreach( $mConfig as $k => $v )
        {
            if( !isset($aReturn[$k]) )
            {
                $aReturn[$k] = $v;
            }
        }

        return $aReturn;
    }

    /**
     * Gets a configuration value
     * @param string The name(s) of the configuration parameter to get
     * @example config::GetValue('database','default','engine')
     * @return NULL|string value of the configuration or null if not found
     */
    public static function GetValue()
    {
        if( func_num_args() > 0 ) {
            $args = func_get_args();
            self::LoadConfig($args[0]);
            $last = self::$Config;
            foreach( $args as $arg ) {
                if( isset($last[$arg]) )
                    $last = $last[$arg];
                else
                    return NULL;
            }
            return $last;
        }
        return NULL;
    }
}

?>