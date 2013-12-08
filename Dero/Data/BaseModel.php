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
}