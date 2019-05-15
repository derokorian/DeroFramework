<?php

/**
 * Factory for Data instances
 *
 * @author    Ryan Pallas
 * @package   SampleSite
 * @namespace App\Model
 * @since     2013-12-15
 */

namespace Dero\Data;

use UnexpectedValueException;

class Factory
{
    /**
     * @param string $InstanceName The name of the connection
     *
     * @return DataInterface
     * @throws UnexpectedValueException
     * @throws \Exception
     *
     */
    public static function GetDataInterface(string $InstanceName): DataInterface
    {
        return new PDOMysql($InstanceName);
    }
}