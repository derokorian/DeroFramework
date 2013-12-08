<?php

namespace Dero\Data;
use Dero\Core\Config;

/**
 * PDO wrapper for MySQL
 * @author Ryan Pallas
 * @package DeroFramework
 * @namespace Dero\Data
 * @see DataInterface
 */
class PDOMysql extends PDOWrapper
{
    private $oInstance;

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
        $sType = NULL;
        if( !is_null(call_user_func_array('Config::GetValue', array_merge($aConfigArgs, 'write'))) &&
            !is_null(call_user_func_array('Config::GetValue', array_merge($aConfigArgs, 'read'))))
        {
            if( $bIsRead )
            {
                if( $this->oInstance['read'] )
                    return $this->oInstance['read'];
                $aConfigArgs[] = $sType = 'read';
            }
            else
            {
                if( $this->oInstance['write'] )
                    return $this->oInstance['write'];
                $aConfigArgs[] = $sType = 'write';
            }
        }
        elseif( !is_null(Config::GetValue('database', $this->sInstance, 'read')))
        {
            if( $this->oInstance['read'] )
                return $this->oInstance['read'];
            $aConfigArgs[] = $sType = 'read';
        }
        elseif( !is_null(Config::GetValue('database', $this->sInstance, 'write')))
        {
            if( $this->oInstance['write'] )
                return $this->oInstance['write'];
            $aConfigArgs[] = $sType = 'write';
        }
        elseif( !is_null(Config::GetValue('database', $this->sInstance, 'name')))
        {
            if( isset($this->oInstance) )
                return $this->oInstance;
        }
        else
        {
            throw new \UnexpectedValueException('Database connection information not properly defined');
        }
        $aOpts['Name'] = call_user_func_array('Config::GetValue', array_merge($aConfigArgs, 'name'));
        $aOpts['Host'] = call_user_func_array('Config::GetValue', array_merge($aConfigArgs, 'host'));
        $aOpts['User'] = call_user_func_array('Config::GetValue', array_merge($aConfigArgs, 'user'));
        $aOpts['Pass'] = call_user_func_array('Config::GetValue', array_merge($aConfigArgs, 'pass'));
        $aOpts['Port'] = call_user_func_array('Config::GetValue', array_merge($aConfigArgs, 'port')) ?: 3306;
        if( in_array(null, $aOpts) )
        {
            throw new \UnexpectedValueException('Database connection information not properly defined');
        }
        if( is_null($sType) )
        {
            $this->oInstance = new \PDO(
                sprintf('mysql:dbname=%s;host=%s;port=%d',$aOpts['Name'],$aOpts['Host'], $aOpts['Port']),
                $aOpts['User'],
                $aOpts['Pass']
            );
            return $this->oInstance;
        }
        else
        {
            $this->oInstance[$sType] = new \PDO(
                sprintf('mysql:dbname=%s;host=%s;port=%d',$aOpts['Name'],$aOpts['Host'], $aOpts['Port']),
                $aOpts['User'],
                $aOpts['Pass']
            );
            return $this->oInstance[$sType];
        }
    }
}

