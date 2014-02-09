<?php

namespace Dero\Core;

class RetVal
{
    private $mRetval = null;
    private $iErrorCode = null;
    private $strError = '';
    private $oException = null;

    public function Set($mVal)
    {
        $this->mRetval = $mVal;
    }

    public function Get()
    {
        return $this->mRetval;
    }

    public function SetError($strMessage, \Exception $oException, $iCode = null)
    {
        $this->strError = $strMessage;
        $this->oException = $oException;
        $this->iErrorCode = $iCode;
    }

    public function HasFailure()
    {
        return strlen($this->strError) > 0 || !is_null($this->oException);
    }

    public function GetError()
    {
        return $this->strError;
    }

    public function GetException()
    {
        return $this->oException;
    }
} 