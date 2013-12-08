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
abstract class PDOWrapper implements DataInterface
{
    protected $oInstance;
    protected $oPDOStatement;
    protected $sInstance;

    /**
     * Constructor for PDO wrapper
     * @param string $Instance name of database connection defined in configuration
     * @throws \InvalidArgumentException
     */
    public function __construct($Instance = NULL)
    {
        if( is_null($Instance) || !is_string($Instance) )
            throw new \InvalidArgumentException(__CLASS__ . ' requires an instance');
        else
            $this->sInstance = $Instance;
    }

    abstract protected function OpenConnection($sConType);

    /**
     * Prepares a query for execution
     * @param string $Query
     */
    abstract public function Prepare($Query);

    /**
     * Executes a query directly
     * @param string $Query
     */
    abstract public function Query($Query);

    /**
     * (non-PHPdoc)
     * @see DataInterface::BindParams()
     * @param ParameterCollection $Params
     * @return PDOMysql allows method chaining
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
     * @return PDOMysql allows method chaining
     * @throws DataException
     */
    public function BindParam(Parameter $Param)
    {
        if(! $this->oPDOStatement ) return $this;
        try {
            $this->oPDOStatement->bindValue($Param->Name, $Param->Value, $Param->Type);
            return $this;
        } catch(\Exception $e) {
            throw new DataException('unable to bind parameter '. $e->getMessage());
        }
    }

    /**
     * (non-PHPdoc)
     * @see DataInterface::Execute()
     * @return PDOMysql allows method chaining
     * @throws DataException
     */
    public function Execute()
    {
        if(! $this->oPDOStatement ) return $this;
        try {
            $this->oPDOStatement->execute();
            return $this;
        } catch( \PDOException $e) {
            throw new DataException('Error executing query in '. __CLASS__ .'::'. __FUNCTION__);
        }
    }

    /**
     * (non-PHPdoc)
     * @see DataInterface::GetRow()
     * @return \StdObject object with properties mapped to selected columns
     */
    public function Get()
    {
        if(! $this->oPDOStatement ) return FALSE;
        return $this->oPDOStatement->fetch(\PDO::FETCH_OBJ);
    }

    /**
     * (non-PHPdoc)
     * @see DataInterface::GetAll()
     * @return Array(StandardObject) array of objects with properties mapped to selected columns
     */
    public function GetAll()
    {
        if(! $this->oPDOStatement ) return FALSE;
        return $this->oPDOStatement->fetchAll(\PDO::FETCH_OBJ);
    }

    /**
     * (non-PHPdoc)
     * @see DataInterface::GetScalar()
     * @return mixed
     */
    public function GetScalar()
    {
        if(! $this->oPDOStatement ) return FALSE;
        return $this->oPDOStatement->fetch(\PDO::FETCH_NUM)[0];
    }
}