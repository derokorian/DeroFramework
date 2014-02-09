<?php

namespace Dero\Data;
use Dero\Core\Config;

/**
 * PDO wrapper for MySQL
 * @author Ryan Pallas
 * @package DeroFramework
 * @namespace dero\Data
 * @see DataInterface
 */
class PDOMysql extends PDOWrapper
{
    public function __construct($Instance = NULL)
    {
        parent::__construct($Instance);

        if( !extension_loaded('pdo_mysql') )
            throw new \Exception('PDO_MySQL is not loaded - please check the server\'s configuration');
    }

    /**
     * Opens a connection for a query
     */
    protected function OpenConnection($bIsRead)
    {
        if( $this->oPDOStatement ) unset($this->oPDOStatement);
        $sType = NULL;
        if( !is_null(Config::GetValue('database', $this->sInstance, 'write')) &&
            !is_null(Config::GetValue('database', $this->sInstance, 'read')))
        {
            if( $bIsRead )
            {
                if( $this->oInstance['read'] )
                    return $this->oInstance['read'];
                $sType = 'read';
            }
            else
            {
                if( $this->oInstance['write'] )
                    return $this->oInstance['write'];
                $sType = 'write';
            }
        }
        elseif( !is_null(Config::GetValue('database', $this->sInstance, 'read')))
        {
            if( $this->oInstance['read'] )
                return $this->oInstance['read'];
            $sType = 'read';
        }
        elseif( !is_null(Config::GetValue('database', $this->sInstance, 'write')))
        {
            if( $this->oInstance['write'] )
                return $this->oInstance['write'];
            $sType = 'write';
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
        if( is_null($sType) )
        {
            $aOpts['Name'] = Config::GetValue('database', $this->sInstance,'name');
            $aOpts['Host'] = Config::GetValue('database', $this->sInstance,'host');
            $aOpts['User'] = Config::GetValue('database', $this->sInstance,'user');
            $aOpts['Pass'] = Config::GetValue('database', $this->sInstance,'pass');
            $aOpts['Port'] = Config::GetValue('database', $this->sInstance,'port') ?: 3306;
            if( in_array(null, $aOpts) )
            {
                throw new \UnexpectedValueException('Database connection information not properly defined');
            }
            try {
                $this->oInstance = new \PDO(
                    sprintf(
                        'mysql:dbname=%s;host=%s;port=%d',
                        $aOpts['Name'],
                        $aOpts['Host'],
                        $aOpts['Port']
                    ),
                    $aOpts['User'],
                    $aOpts['Pass']
                );
                return $this->oInstance;
            } catch (\PDOException $e) {
                throw new DataException("Unable to open database connection\n" . var_export($aOpts, true), 0, $e);
            }
        }
        else
        {
            $aOpts['Name'] = Config::GetValue('database', $this->sInstance, $sType, 'name');
            $aOpts['Host'] = Config::GetValue('database', $this->sInstance, $sType, 'host');
            $aOpts['User'] = Config::GetValue('database', $this->sInstance, $sType, 'user');
            $aOpts['Pass'] = Config::GetValue('database', $this->sInstance, $sType, 'pass');
            $aOpts['Port'] = Config::GetValue('database', $this->sInstance, $sType, 'port') ?: 3306;
            if( in_array(null, $aOpts) )
            {
                throw new \UnexpectedValueException('Database connection information not properly defined');
            }
            try {
                $this->oInstance[$sType] = new \PDO(
                    sprintf(
                        'mysql:dbname=%s;host=%s;port=%d',
                        $aOpts['Name'],
                        $aOpts['Host'],
                        $aOpts['Port']
                    ),
                    $aOpts['User'],
                    $aOpts['Pass']
                );
                return $this->oInstance[$sType];
            } catch (\PDOException $e) {
                throw new DataException("Unable to open database connection\n" . var_export($aOpts, true), 0, $e);
            }
        }
    }

    public function BindParam(Parameter $Param)
    {
        $Param->SetEngine(DB_ENG_MYSQL);
        parent::BindParam($Param);
    }
}

