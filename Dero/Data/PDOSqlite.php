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

class PDOSqlite extends PDOWrapper
{
    private $oInstance;

    public function __construct($Instance = NULL)
    {
        parent::__construct($Instance);

        if( !extension_loaded('pdo_sqlite') )
            throw new \Exception('PDO_SQLITE is not loaded - please check the server\'s configuration');
    }

    /**
     * Opens a connection for a query
     */
    protected function OpenConnection($bIsRead){
        if( is_null(Config::GetValue('database', $this->sInstance, 'dbLocation')) )
        {
            throw new \UnexpectedValueException('Database configuration missing or not correct');
        }
        if( !$this->oInstance )
            $this->oInstance = new PDO(sprintf('sqlite:%s', Config::GetValue('database', $this->sInstance, 'dbLocation')));
        return $this->oInstance;
    }
}