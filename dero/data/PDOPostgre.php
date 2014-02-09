<?php

namespace Dero\Data;
use Dero\Core\Config;

/**
 * PDO wrapper for PostgreSQL
 * @author Ryan Pallas
 * @package DeroFramework
 * @namespace dero\Data
 * @since 2012-12-08
 */

class PDOPostgre extends PDOWrapper
{
    public function __construct($Instance = NULL)
    {
        parent::__construct($Instance);

        if( !extension_loaded('pdo_pgsql') )
            throw new \Exception('PDO_PGSQL is not loaded - please check the server\'s configuration');
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
        $aOpts = [];
        $aOpts['Name'] = call_user_func_array('config::GetValue', array_merge($aConfigArgs, ['name']));
        $aOpts['Host'] = call_user_func_array('config::GetValue', array_merge($aConfigArgs, ['host']));
        $aOpts['User'] = call_user_func_array('config::GetValue', array_merge($aConfigArgs, ['user']));
        $aOpts['Pass'] = call_user_func_array('config::GetValue', array_merge($aConfigArgs, ['pass']));
        $aOpts['Port'] = call_user_func_array('config::GetValue', array_merge($aConfigArgs, ['port'])) ?: 3306;
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