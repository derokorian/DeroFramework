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
class PDOMySQL implements DataInterface
{
    private $oReadInstance;
    private $oWriteInstance;
    private $stmt;
    private $inst;

    /**
     * Constructor for MySQL wrapper
     * @param string $Instance name of database connection defined in configuration
     * @throws \InvalidArgumentException
     */
    public function __construct($Instance = NULL)
    {
        if( is_null($Instance) || !is_string($Instance) )
            throw new \InvalidArgumentException('PDOMysql requires an instance');
        else
            $this->inst = $Instance;
    }

    /**
     * Opens a connection for read-only operations
     * @throws DataException
     */
    private function OpenReadConnection()
    {
        if( !isset($this->oReadInstance) ) {
            try {
                $dsn = 'mysql:dbname=' . Config::GetValue('database', $this->inst, 'read', 'name')
                        . ';port=' . Config::GetValue('database', $this->inst, 'read', 'port')
                        . ';host=' . Config::GetValue('database', $this->inst, 'read', 'host');
                $this->oReadInstance = new \PDO($dsn,
                        Config::GetValue('database', $this->inst, 'read', 'User'),
                        Config::GetValue('database', $this->inst, 'read', 'pass'));
            } catch (\Exception $e) {
                throw new DataException('Database connectivity error.');
            }
        }
        return $this->oReadInstance;
    }

    /**
     * Opens a connection to allow write operations
     * @throws DataException
     */
    private function OpenWriteConnection()
    {
        if( !isset(self::$_WriteInstance) ) {
            try {
                $dsn = 'mysql:dbname=' . Config::GetValue('database', $this->inst, 'write', 'name')
                        . ';port=' . Config::GetValue('database', $this->inst, 'write', 'port')
                        . ';host=' . Config::GetValue('database', $this->inst, 'write', 'host');
                $this->oWriteInstance = new \PDO($dsn,
                        Config::GetValue('database', $this->inst, 'write', 'User'),
                        Config::GetValue('database', $this->inst, 'write', 'pass'));
            } catch (\Exception $e) {
                throw new DataException('Database connectivity error.');
            }
        }
        return $this->oWriteInstance;
    }

    /**
     * Opens a connection for a query
     */
    private function OpenConnection($bIsRead)
    {
        if( $this->stmt ) unset($this->stmt);
        if( $bIsRead )
            return $this->OpenReadConnection();
        else
            return $this->OpenWriteConnection();
    }

    /**
     * (non-PHPdoc)
     * @param string $Query
     * @return Mysql allows method chaining
     * @throws DataException
     */
    public function Prepare($Query)
    {
        $oCon = $this->OpenConnection(substr(trim($Query), 0, 6) == 'SELECT');
        try
        {
            $this->stmt = $oCon->prepare($Query);
            if( $this->stmt === FALSE )
            {
                $e = $oCon->errorInfo();
                throw new DataException('Error preparing query in '. __CLASS__ . '::'
                    . __FUNCTION__ . '(' . $e[2] . ')');
            }
            return $this;
        }
        catch (\Exception $e)
        {
            throw new DataException('Error preparing query in '. __CLASS__ . '::' . __FUNCTION__);
        }

    }

    /**
     * (non-PHPdoc)
     * @param string $Query
     * @return Mysql allows method chaining
     * @throws DataException
     */
    public function Query($Query)
    {
        $oCon = $this->OpenConnection(substr(trim($Query), 0, 6) == 'SELECT');
        try
        {
            $this->stmt = $oCon->query($Query);
            if( $this->stmt === FALSE )
            {
                $e = $oCon->errorInfo();
                throw new DataException('Error preparing query in '. __CLASS__ . '::'
                    . __FUNCTION__ . '(' . $e[2] . ')');
            }
            return $this;
        }
        catch (\Exception $e)
        {
            throw new DataException('Error preparing query in '. __CLASS__ . '::' . __FUNCTION__);
        }
    }

    /**
     * (non-PHPdoc)
     * @see DataInterface::BindParams()
     * @param ParameterCollection $Params
     * @return Mysql allows method chaining
     * @throws DataException
     */
    public function BindParams(ParameterCollection $Params)
    {
        foreach( $Params as $Param ) {
            $this->BindParam($Param);
        }
        return $this;
    }

    /**
     * (non-PHPdoc)
     * @see DataInterface::BindParam()
     * @param Parameter $Param
     * @return Mysql allows method chaining
     * @throws DataException
     */
    public function BindParam(Parameter $Param)
    {
        if(! $this->stmt ) return $this;
        try {
            $this->stmt->bindValue($Param->Name, $Param->Value, $Param->Type);
            return $this;
        } catch(\Exception $e) {
            throw new DataException('unable to bind parameter '. $e->getMessage());
        }
    }

    /**
     * (non-PHPdoc)
     * @see DataInterface::Execute()
     * @return Mysql allows method chaining
     * @throws DataException
     */
    public function Execute()
    {
        if(! $this->stmt ) return $this;
        try {
            $this->stmt->execute();
            return $this;
        } catch( \PDOException $e) {
            throw new DataException('Error executing query in '. __CLASS__ .'::'. __FUNCTION__);
        }
    }

    /**
     * (non-PHPdoc)
     * @see DataInterface::GetRow()
     * @return StandardObject object with properties mapped to selected columns
     */
    public function Get()
    {
        if(! $this->stmt ) return FALSE;
        return $this->stmt->fetch(\PDO::FETCH_OBJ);
    }

    /**
     * (non-PHPdoc)
     * @see DataInterface::GetAll()
     * @return Array(StandardObject) array of objects with properties mapped to selected columns
     */
    public function GetAll()
    {
        if(! $this->stmt ) return FALSE;
        return $this->stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    /**
     * (non-PHPdoc)
     * @see DataInterface::GetScalar()
     * @return mixed
     */
    public function GetScalar()
    {
        if(! $this->stmt ) return FALSE;
        return $this->stmt->fetch(\PDO::FETCH_NUM)[0];
    }
}

