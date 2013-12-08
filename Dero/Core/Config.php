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
        if( !array_key_exists($file,self::$Config) ) {
            $config = [];
            if( is_readable(ROOT . DS . 'config' . DS . $file . '.php') )
                include ROOT . DS . 'config' . DS . $file . '.php';
            self::$Config = array_merge(self::$Config,$config);
        }
    }

    /**
     * Gets a configuration value
     * @param string The name(s) of the configuration parameter to get
     * @example Config::GetValue('database','default','engine')
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