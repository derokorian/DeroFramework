<?php

namespace Dero\Data;

/**
 * Contract to define what should be available to all data interface classes
 *
 * @author Ryan Pallas
 */
interface DataInterface
{
    /**
     * Prepares a query for execution
     *
     * @param string $Query
     *
     * @return DataInterface
     */
    public function Prepare(string $Query): DataInterface;

    /**
     * Executes a query directly
     *
     * @param string $Query
     *
     * @return DataInterface
     */
    public function Query(string $Query): DataInterface;

    /**
     * Binds a collection of parameters to a prepared query
     *
     * @param ParameterCollection $Params
     *
     * @return DataInterface
     */
    public function BindParams(ParameterCollection $Params): DataInterface;

    /**
     * Binds a single parameter to a prepared query
     *
     * @param Parameter $Param
     *
     * @return DataInterface
     */
    public function BindParam(Parameter $Param): DataInterface;

    /**
     * Executes a prepared query
     *
     * @return DataInterface
     */
    public function Execute(): DataInterface;

    /**
     * Gets a single row from a result set
     *
     * @param string|object $mClass
     */
    public function Get($mClass = null);

    /**
     * Gets all rows in a result set
     *
     * @param string $strClass
     */
    public function GetAll($strClass = null);

    /**
     * Gets a singular value from the first row and column
     */
    public function GetScalar();

    /**
     * Gets the row count from the statement object
     */
    public function RowCount();
}
