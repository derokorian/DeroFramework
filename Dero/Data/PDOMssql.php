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
    public function __construct($Instance = NULL)
    {
        parent::__construct($Instance);

        if( !extension_loaded('pdo_sqlsrv') )
            throw new \Exception('PDO_SQLSRV is not loaded - please check the server\'s configuration');
    }

    /**
     * Opens a connection for a query
     */
    protected function OpenConnection($bIsRead)
    {
        if( $this->oPDOStatement ) unset($this->oPDOStatement);
        $aConfigArgs = [
            'database',
            $this->sInstance
        ];
        $aOpts['Name'] = call_user_func_array('Config::GetValue', array_merge($aConfigArgs, ['name']));
        $aOpts['Host'] = call_user_func_array('Config::GetValue', array_merge($aConfigArgs, ['host']));
        $aOpts['User'] = call_user_func_array('Config::GetValue', array_merge($aConfigArgs, ['user']));
        $aOpts['Pass'] = call_user_func_array('Config::GetValue', array_merge($aConfigArgs, ['pass']));
        $aOpts['Port'] = call_user_func_array('Config::GetValue', array_merge($aConfigArgs, ['port'])) ?: 1433;
        if( in_array(null, $aOpts) )
        {
            throw new \UnexpectedValueException('Database connection information not properly defined');
        }
        if( !$this->oInstance )
        {
            $this->oInstance = new \PDO(
                sprintf(
                    'sqlsrv:host=%s,%d;dbname=%s;user=%s;password=%s',
                    $aOpts['Host'],
                    $aOpts['Port'],
                    $aOpts['Name']
                ),
                $aOpts['User'],
                $aOpts['Pass']
            );
        }
        return $this->oInstance;
    }
}