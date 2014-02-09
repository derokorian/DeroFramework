<?php

namespace Dero\Data;

/**
 * Base Model class from which all models inherit
 * @author Ryan Pallas
 */
abstract class BaseModel
{
    protected $DB;
    protected static $TABLE_NAME = '';
    protected static $COLUMNS = [];

    /**
     * Initializes a instance of BaseModel
     * @param DataInterface $DB
     */
    public function __construct(DataInterface $DB)
    {
        $this->DB = $DB;
    }

    /**
     * Returns either WHERE or AND for sql
     * First call after resetting returns WHERE, the rest returns AND
     * @param bool $reset True to return where on next call
     * @return void|string
     */
    private function where($reset = FALSE)
    {
        static $Where;
        if( $reset ) {
            $Where = FALSE;
            return null;
        }
        if( $Where === FALSE ) {
            $Where = TRUE;
            return 'WHERE ';
        }
        return 'AND ';
    }

    protected function GenerateCriteria(ParameterCollection &$oParams, Array $aOpts)
    {
        $this->where(true);
        $sql = '';
        foreach( static::$COLUMNS as $name => $def)
        {
            if( isset($aOpts[$name]) )
            {
                $type = $this->getParamTypeFromColType($aOpts[$name], $def);
                if( $type === DB_PARAM_NULL )
                    $sql .= $this->where() . sprintf('%s IS :%s ', $name, $name);
                else
                    $sql .= $this->where() . sprintf('%s=:%s ', $name, $name);
                $oParams->Add(new Parameter($name, $aOpts[$name], $type));
            }
        }
        if( isset($aOpts['order_by']) && isset(static::$COLUMNS[$aOpts['order_by']]) )
            $sql .= 'ORDER BY ' . $aOpts['order_by'];

        if( isset($aOpts['rows']) )
        {
            $sql .= 'LIMIT :rows ';
            $oParams->Add(new Parameter('rows', $aOpts['rows']));
        }
        else
        {
            $sql .= 'OFFSET :skip ';
            $oParams->Add(new Parameter('skip', $aOpts['skip']));
        }
        return $sql;
    }

    protected function getParamTypeFromColType($val, $def)
    {
        $return = NULL;
        if( isset($def['nullable']) && $def['nullable'] && $val === NULL)
            $return = DB_PARAM_NULL;
        elseif( isset($def['col_type']) )
        {
            if( $def['col_type'] == COL_TYPE_BOOLEAN )
                $return = DB_PARAM_BOOL;
            elseif( $def['col_type'] == COL_TYPE_INTEGER )
                $return = DB_PARAM_INT;
            elseif( $def['col_type'] == COL_TYPE_DECIMAL )
                $return = DB_PARAM_DEC;
            else
                $return = DB_PARAM_STR;
        }
        if( $return === NULL )
            throw new \UnexpectedValueException('Unknown column definition');
        return $return;
    }

    public function GenerateCreateTable()
    {
        if( strlen(static::$TABLE_NAME) == 0 || count(static::$COLUMNS) == 0)
            return null;

        $strCreate = '';
        if( $this->DB instanceof PDOMysql )
        {
            $strCreate .= 'CREATE TABLE IF NOT EXISTS ' . static::$TABLE_NAME . '(';
            foreach( static::$COLUMNS as $strCol => $aCol )
            {
                $sType = '';
                $sNull = 'NULL';
                $sKey = '';
                $sExtra = '';
                switch($aCol['col_type'])
                {
                    case COL_TYPE_BOOLEAN:
                        $sType = 'TINYINT(1)';
                        break;
                    case COL_TYPE_DATETIME;
                        $sType = 'DATETIME';
                        break;
                    case COL_TYPE_DECIMAL:
                        if( isset($aCol['precision']) && isset($aCol['scale']) )
                        {
                            $sType = sprintf('DECIMAL(%d, %d)', $aCol['precision'], $aCol['scale']);
                        }
                        else
                        {
                            $sType = 'DECIMAL(10, 4)';
                        }
                        break;
                    case COL_TYPE_INTEGER:
                        $sType = 'INT';
                        break;
                    case COL_TYPE_TEXT:
                        $sType = 'TEXT';
                        break;
                }
                if( !$aCol['nullable'] )
                {
                    $sNull = 'NOT NULL';
                }
                if( in_array('auto_increment', $aCol['extra']) )
                {
                    $sExtra = 'auto_increment';
                }
                switch($aCol['key'])
                {
                    case KEY_TYPE_PRIMARY:
                        $sKey = 'primary';
                        break;
                    case KEY_TYPE_FOREIGN:
                        if( isset($aCol['foreign_table']) &&
                            isset($aCol['foreign_column']) )
                        {
                            $sKey = sprintf("FOREIGN KEY %s_%s (%s) REFERENCES %s (%s)");
                        }
                }
                $strCreate .= sprintf(
                    "\n\t`%s` %s %s %s %s,",
                    $strCol,
                    $sType,
                    $sNull,
                    $sExtra,
                    $sKey
                );
            }
        }
        $strCreate = substr($strCreate, 0, -1) . ') Engine=InnoDB';
        return $strCreate;
    }
}