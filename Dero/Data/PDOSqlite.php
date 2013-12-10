<?php

namespace Dero\Data;
use Dero\Core\Config;

/**
 * PDO wrapper for SQLite
 * @author Ryan Pallas
 * @package DeroFramework
 * @namespace Dero\Data
 * @since 2012-12-08
 */

abstract class PDOSqlite extends PDOWrapper
{
    private $oInstance;

    /**
     * Opens a connection for a query
     */
    protected function OpenConnection($bIsRead){
        if( is_null(Config::GetValue('database', $this->sInstance, 'dbLocation')) )
        {
            throw new \UnexpectedValueException('Database configuration missing or not correct');
        }
        return $this->oInstance = new PDO(sprintf('sqlite:%s', Config::GetValue('database', $this->sInstance, 'dbLocation')));
    }
}