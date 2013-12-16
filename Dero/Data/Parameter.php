<?php

namespace Dero\Data;

/**
 * Classed used to define the parameters for prepared queries
 *
 * @author Ryan Pallas
 *
 * @property str Name
 * @property str|int|null|bool Value
 * @property int Type
 * @property int Engine
 */
class Parameter
{
    private $Name = '';
    private $Value = '';
    private $Type = 0;
    private $Engine = 0;

    /**
     * Construct for creating new Parameter
     * @param string $Name the name of the parameter
     * @param string $Value the value to be bound
     * @param int $Type [Optional] Value type (DB_PARAM_INT,DB_PARAM_STR,DB_PARAM_BOOL,DB_PARAM_NULL) [Default: DB_PARAM_STR]
     * @param int $Engine [Optional] Data engine (DB_ENG_MYSQL,DB_ENG_MSSQL,DB_ENG_POSTGRE, DB_ENG_SQLITE) [Defaults to default database engine]
     */
    public function __construct($Name, $Value, $Type = NULL, $Engine = NULL)
    {
        $this->SetName($Name);
        $this->SetValue($Value);
        if( !is_null($Type) ) {
            $this->SetType($Type);
        }
        if( !is_null($Engine) ) {
            $this->SetEngine($Engine);
        } else {
            $this->SetEngine(\Dero\Core\Config::GetValue('database','default','engine'));
        }
    }

    public function __get($name)
    {
        if( method_exists($this, "Get$name") ) {
            return $this->{"Get$name"}();
        }
        throw new \UnexpectedValueException('Undefined property '.$name.' in '.__CLASS__);
    }

    public function __set($name, $value)
    {
        if( method_exists($this, "Set$name") ) {
            return $this->{"Set$name"}($value);
        }
        throw new \UnexpectedValueException('Undefined property '.$name.' in '.__CLASS__);
    }

    /**
     * Sets the name of the parameter
     * @param string $Name
     * @throws \InvalidArgumentException
     * @return void
     */
    public function SetName($Name)
    {
        if( empty($Name) || !is_string($Name) )
            throw new \InvalidArgumentException('name of parameters must be a string');
        $this->Name = $Name;
    }

    /**
     * Gets the name of the parameter
     * @throws \UnexpectedValueException
     * @return string
     */
    public function GetName()
    {
        switch($this->Engine)
        {
            case DB_ENG_MYSQL:
                return ':' . $this->Name;
            case DB_ENG_MSSQL:
                switch($this->Type) {
                    default:
                        throw new \UnexpectedValueException('Parameter does not yet support MS SQL Server');
                }
                break;
            case DB_ENG_POSTGRE:
                switch($this->Type) {
                    default:
                        throw new \UnexpectedValueException('Parameter does not yet support PostgreSQL');
                }
                break;
            case DB_ENG_SQLITE:
                switch($this->Type) {
                    default:
                        throw new \UnexpectedValueException('Parameter does not yet support SQLite');
                }
                break;
            default:
                throw new \UnexpectedValueException('Unexpected value found in Parameter::Engine');
        }
    }

    /**
     * Sets the value to be bound
     * @param str|int|null|bool $Value
     * @return void
     */
    public function SetValue($Value)
    {
        if( is_bool($Value) )
            $this->SetType(DB_PARAM_BOOL);
        elseif( is_null($Value) )
            $this->SetType(DB_PARAM_NULL);
        elseif( is_int($Value) )
            $this->SetType(DB_PARAM_INT);
        else
            $this->SetType(DB_PARAM_STR);

        $this->Value = $Value;
    }

    /**
     * Gets the value of the parameter
     * @return string
     */
    public function GetValue()
    {
        return $this->Value;
    }

    /**
     * Sets the type of the parameter
     * @param int $Type One of (DB_PARAM_STR, DB_PARAM_INT, DB_PARAM_BOOl, DB_PARAM_NULL)
     * @throws \InvalidArgumentException
     * @return void
     */
    public function SetType($Type)
    {
        $acceptable = [DB_PARAM_BOOL, DB_PARAM_INT, DB_PARAM_NULL, DB_PARAM_STR];
        if( !in_array($Type, $acceptable, TRUE) )
            throw new \InvalidArgumentException('type of parameters must be DB_PARAM_*');
        $this->Type = $Type;
    }

    /**
     * Gets the type of the parameter, determined by the Engine
     * @throws \UnexpectedValueException
     * @return number
     */
    public function GetType()
    {
        switch($this->Engine) {
            case DB_ENG_MYSQL:
                switch($this->Type) {
                    case DB_PARAM_INT:
                        return \PDO::PARAM_INT;
                    case DB_PARAM_BOOL:
                        return \PDO::PARAM_BOOL;
                    case DB_PARAM_NULL:
                        return \PDO::PARAM_NULL;
                    case DB_PARAM_STR:
                        return \PDO::PARAM_STR;
                    default:
                        throw new \UnexpectedValueException('Unexpected value found in Parameter::Type for MySQL');
                }
                break;
            case DB_ENG_MSSQL:
                switch($this->Type) {
                    default:
                        throw new \UnexpectedValueException('Parameter does not yet support MS SQL Server');
                }
                break;
            case DB_ENG_POSTGRE:
                switch($this->Type) {
                    default:
                        throw new \UnexpectedValueException('Parameter does not yet support PostgreSQL');
                }
                break;
            case DB_ENG_SQLITE:
                switch($this->Type) {
                    default:
                        throw new \UnexpectedValueException('Parameter does not yet support SQLite');
                }
                break;
            default:
                throw new \UnexpectedValueException('Unexpected value found in Parameter::Engine');
        }
    }

    /**
     * Sets the engine type being used
     * @param int $Engine One of (DB_ENG_MYSQL, DB_ENG_MSSQL, DB_ENG_ORACLE)
     * @throws \InvalidArgumentException
     * @return void
     */
    public function SetEngine($Engine)
    {
        $acceptable = [DB_ENG_MYSQL, DB_ENG_MSSQL, DB_ENG_POSTGRE, DB_ENG_SQLITE];
        if( !in_array($Engine, $acceptable, TRUE) )
            throw new \InvalidArgumentException('type of parameters must be DB_ENG_*');
        $this->Engine = $Engine;
    }

}

?>