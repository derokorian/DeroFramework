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
     * Prepares a query for execution
     * @param string $Query
     */
    public function Prepare($Query){}

    /**
     * Executes a query directly
     * @param string $Query
     */
    public function Query($Query){}
}