<?php

/**
 * Factory for Data instances
 * @author Ryan Pallas
 * @package SampleSite
 * @namespace App\Model
 * @since 2013-12-15
 */

namespace Dero\Data;
use Dero\Core\Config;

class Factory
{
    /**
     * @throws \UnexpectedValueException
     * @param string $InstanceName The name of the connection
     * @return DataInterface
     */
    public static function GetDataInterface($InstanceName)
    {
        $Engine = Config::GetValue('database', $InstanceName, 'engine');
        switch($Engine)
        {
            case DB_ENG_MYSQL:
                return new PDOMysql($InstanceName);
            case DB_ENG_MSSQL:
                return new PDOMssql($InstanceName);
            case DB_ENG_POSTGRE:
                return new PDOPostgre($InstanceName);
            case DB_ENG_SQLITE:
                return new PDOSqlite($InstanceName);
            default:
                throw new \UnexpectedValueException('Unexpected value found in Parameter::Engine');
        }
    }
}