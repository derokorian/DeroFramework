<?php

namespace Dero\Data;
use Dero\Core\Config;

/**
 * PDO wrapper for PostgreSQL
 * @author Ryan Pallas
 * @package DeroFramework
 * @namespace Dero\Data
 * @since 2012-12-08
 */

abstract class PDOPostgre extends PDOWrapper
{
    private $oInstance;

    /**
     * Opens a connection for a query
     */
    protected function OpenConnection($bIsRead)
    {
        $aConfigArgs = [
            'database',
            $this->sInstance
        ];
        $aOpts = [];
        $aOpts['Name'] = call_user_func_array('Config::GetValue', array_merge($aConfigArgs, 'name'));
        $aOpts['Host'] = call_user_func_array('Config::GetValue', array_merge($aConfigArgs, 'host'));
        $aOpts['User'] = call_user_func_array('Config::GetValue', array_merge($aConfigArgs, 'user'));
        $aOpts['Pass'] = call_user_func_array('Config::GetValue', array_merge($aConfigArgs, 'pass'));
        $aOpts['Port'] = call_user_func_array('Config::GetValue', array_merge($aConfigArgs, 'port')) ?: 3306;
        if( in_array(null, $aOpts) )
        {
            throw new \UnexpectedValueException('Database connection information not properly defined');
        }
        if( !$this->oInstance )
        {
            $this->oInstance = new \PDO(
                sprintf(
                    'pgsql:host=%s;port=%d;dbname=%s;user=%s;password=%s',
                    $aOpts['Host'],
                    $aOpts['Port'],
                    $aOpts['Name'],
                    $aOpts['User'],
                    $aOpts['Pass']
                )
            );
        }
        return $this->oInstance;
    }
}