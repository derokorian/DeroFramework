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

    /**
     * Generates the SQL to create table defined by class properties
     * @return null|string
     * @throws \UnexpectedValueException
     */
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
                        if( isset($aCol['col_length']) && isset($aCol['scale']) )
                        {
                            $sType = sprintf('DECIMAL(%d, %d)', $aCol['col_length'], $aCol['scale']);
                        }
                        else
                        {
                            $sType = 'DECIMAL(10, 4)';
                        }
                        break;
                    case COL_TYPE_INTEGER:
                        $sType = 'INT';
                        if( isset($aCol['col_length']) && is_numeric($aCol['col_length']) )
                        {
                            $sType .= sprintf("(%d)", $aCol['col_length']);
                        }
                        break;
                    case COL_TYPE_TEXT:
                        $sType = 'TEXT';
                        break;
                    case COL_TYPE_STRING:
                        if( isset($aCol['col_length']) && is_numeric($aCol['col_length']) )
                        {
                            $sType = sprintf("VARCHAR(%d)", $aCol['col_length']);
                        }
                        else
                        {
                            throw new \UnexpectedValueException(
                                'Bad column definition. COL_TYPE_STRING requires col_length be set.');
                        }
                        break;
                    case COL_TYPE_FIXED_STRING:
                        if( isset($aCol['col_length']) && is_numeric($aCol['col_length']) )
                        {
                            $sType = sprintf("CHAR(%d)", $aCol['col_length']);
                        }
                        else
                        {
                            throw new \UnexpectedValueException(
                                'Bad column definition. COL_TYPE_STRING requires col_length be set.');
                        }
                }
                if( isset($aCol['extra']) &&
                    is_array($aCol['extra']) )
                {
                    if( in_array('nullable', $aCol['extra']) )
                    {
                        $sExtra .= 'NULL ';
                    }
                    else
                    {
                        $sExtra .= 'NOT NULL ';
                    }

                    if( in_array('auto_increment', $aCol['extra']) )
                    {
                        $sExtra .= 'auto_increment ';
                    }
                    if( in_array('unique', $aCol['extra']) )
                    {
                        $sExtra .= 'UNIQUE ';
                    }
                    $def = preg_grep('/^default.*$/i', $aCol['extra']);
                    if( count($def) > 0 )
                    {
                        $sExtra .= $def[0];
                    }
                }
                else
                {
                    $sExtra .= 'NOT NULL ';
                }

                if( isset($aCol['key']) )
                {
                    switch($aCol['key'])
                    {
                        case KEY_TYPE_PRIMARY:
                            $sKey = 'PRIMARY KEY';
                            break;
                        case KEY_TYPE_FOREIGN:
                            if( isset($aCol['foreign_table']) &&
                                isset($aCol['foreign_column']) )
                            {
                                $sKey = sprintf(
                                    ",\n\t\tFOREIGN KEY %s_%s (%s)\n\t\t\tREFERENCES %s (%s)",
                                    $aCol['foreign_table'],
                                    $aCol['foreign_column'],
                                    $strCol,
                                    $aCol['foreign_table'],
                                    $aCol['foreign_column']
                                );
                            }
                    }
                }

                $strCreate .= sprintf(
                    "\n\t`%s` %s %s %s,",
                    $strCol,
                    $sType,
                    $sExtra,
                    $sKey
                );
            }
        }
        $strCreate = substr($strCreate, 0, -1) . ') Engine=InnoDB';
        return $strCreate;
    }


    /**
     * Creates the blog-post table
     * @returns \Dero\Core\RetVal
     */
    public function CreateTable()
    {
        $oRetVal = new \Dero\Core\RetVal();
        $strSql = $this->GenerateCreateTable();
        try {
            $oRetVal->Set($this->DB->Query($strSql));
        } catch (DataException $e) {
            $oRetVal->SetError('Unable to query database', $e);
        }
        return $oRetVal;
    }
}