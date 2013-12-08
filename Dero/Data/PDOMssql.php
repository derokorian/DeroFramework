<?php

namespace Dero\Data;
use Dero\Core\Config;

/**
 * PDO wrapper for Microsoft SQL Server
 * @author Ryan Pallas
 * @package DeroFramework
 * @namespace Dero\Data
 * @since 2012-12-08
 */

abstract class PDOMssql extends PDOWrapper
{
    /**
     * Opens a connection for a query
     */
    protected function OpenConnection($bIsRead){}
}