<?php

namespace Dero\Data;

/**
 * Contract to define what should be available to all data interface classes
 * @author Ryan Pallas
 */
interface DataInterface
{
    /**
     * Prepares a query for execution
     * @param string $Query
     */
    public function Prepare($Query);

    /**
     * Executes a query directly
     * @param string $Query
     */
    public function Query($Query);

    /**
     * Binds a collection of parameters to a prepared query
     * @param ParameterCollection $Params
     */
    public function BindParams(ParameterCollection $Params);

    /**
     * Binds a single parameter to a prepared query
     * @param Parameter $Param
     */
    public function BindParam(Parameter $Param);

    /**
     * Executes a prepared query
     */
    public function Execute();

    /**
     * Gets a single row from a result set
     */
    public function Get();

    /**
     * Gets all rows in a result set
     */
    public function GetAll();

    /**
     * Gets a singular value from the first row and column
     */
    public function GetScalar();
}

?>