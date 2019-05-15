<?php

namespace Dero\Core;

use Exception;

/**
 * Class Retval
 *
 * A single return value type that can be used to return multiple
 *   error conditions or a singular return type. Simply put, an
 *   easier way to handle a function with both successful return
 *   values and a multiple types of error conditions (such as a
 *   model which may not have a db connection, bad sql, or a
 *   validation function, which might return multiple things as
 *   wrong).
 *
 * @package Dero\Core
 */
class Retval
{
    private $mRetval = null;
    private $strError = [];
    private $oException = [];

    /**
     * Sets the return value
     *
     * @param $mVal
     */
    public function set($mVal)
    {
        $this->mRetval = $mVal;
    }

    /**
     * Gets the return value
     *
     * @return null
     */
    public function get()
    {
        return $this->mRetval;
    }

    /**
     * Adds an error condition
     *
     * @param                 $strMessage
     * @param Exception|null  $oException
     */
    public function addError($strMessage, Exception $oException = null)
    {
        $this->strError[] = $strMessage;
        $this->oException[] = $oException;
    }

    /**
     * Check if the return value has an error condition
     *
     * @return bool
     */
    public function hasFailure()
    {
        return count($this->strError) > 0;
    }

    /**
     * Gets the error(s) on the return value
     *
     * @return array|null
     */
    public function getError()
    {
        return count($this->strError) == 0 ? null :
            (count($this->strError) == 1 ? $this->strError[0] :
                $this->strError);
    }

    /**
     * Gets the exception(s) on the return value
     *
     * @return array|null
     */
    public function getException()
    {
        return count($this->oException) == 0 ? null :
            (count($this->oException) == 1 ? $this->oException[0] :
                $this->oException);
    }
}
