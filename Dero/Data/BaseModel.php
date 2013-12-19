<?php

namespace Dero\Data;

/**
 * Base Model class from which all models inherit
 * @author Ryan Pallas
 */
abstract class BaseModel
{
    protected $DB;

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
    protected function where($reset = FALSE)
    {
        static $Where;
        if( $reset ) {
            $Where = FALSE;
            return;
        }
        if( $Where === FALSE ) {
            $Where = TRUE;
            return 'WHERE ';
        }
        return 'AND ';
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
            else
                $return = DB_PARAM_STR;
        }
        if( $return === NULL )
            throw new \UnexpectedValueException('Unknown column definition');
        return $return;
    }
}