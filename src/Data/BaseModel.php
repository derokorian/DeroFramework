<?php

namespace Dero\Data;

use Dero\Core\Retval;

/**
 * Base Model class from which all models inherit
 *
 * @author Ryan Pallas
 */
abstract class BaseModel
{
    /** @var string */
    const TABLE_NAME = '';
    /** @var array */
    const COLUMNS = [];
    /** @var array */
    const SUB_OBJECTS = [];
    /** @const Used in queries that do a group concatenation */
    const CONCAT_SEPARATOR = 0x1D;
    const UNIQUE_CONSTRAINT_VIOLATION = 1062;
    const TABLE_CREATED = 'Table successfully created';
    /** @var DataInterface */
    protected $DB;

    /**
     * Initializes a instance of BaseModel
     *
     * @param DataInterface $DB
     */
    public function __construct(DataInterface $DB = null)
    {
        if (!$DB instanceof DataInterface) {
            $DB = Factory::GetDataInterface('default');
        }
        $this->DB = $DB;
    }

    /**
     * Gets data from a table
     *
     * @param array  $aOpts
     * @param string $sTable
     *
     * @return Retval
     */
    public function Get(array $aOpts = [], $sTable = null)
    {
        $oRet = new Retval();
        $aCols = $this->getColsFromTable($sTable);
        if (!is_array($aCols)) {
            // Uh oh, object name provided is not valid, return an error
            $oRet->AddError(sprintf(
                                'Unrecognized table provided to ' . get_called_class() . '::' . __FUNCTION__ . "($sTable)"
                            ));

            return $oRet;
        }
        $oParams = new ParameterCollection();
        $sSql = sprintf(
            'SELECT `%s` FROM `%s` %s',
            implode('`,`', array_keys($aCols)),
            $sTable,
            $this->GenerateCriteria($oParams, $aOpts, '', $aCols)
        );
        try {
            $oRet->Set(array_map(function ($oItem) use ($aCols) {
                foreach ($aCols as $sCol => $aCol) {
                    switch ($aCol[COL_TYPE]) {
                        case COL_TYPE_INTEGER:
                            $oItem->$sCol = (int) $oItem->$sCol;
                            break;
                        case COL_TYPE_BOOLEAN:
                            $oItem->$sCol = (bool) $oItem->$sCol;
                            break;
                        case COL_TYPE_DECIMAL:
                            $oItem->$sCol = (float) $oItem->$sCol;
                            break;
                        // other types are strings, no cast required
                    }

                    return $oItem;
                }
            }, $this->DB
                   ->Prepare($sSql)
                   ->BindParams($oParams)
                   ->Execute()
                   ->GetAll()));
        } catch (DataException $e) {
            $oRet->AddError('Unable to query database', $e);
        }

        return $oRet;
    }

    /**
     * @param $sTable
     *
     * @return array|null
     */
    protected function getColsFromTable(&$sTable)
    {
        if (empty($sTable)) {
            $sTable = static::TABLE_NAME;
        }
        if ($sTable === static::TABLE_NAME) {
            return static::COLUMNS;
        }
        foreach (static::SUB_OBJECTS as $sSubObject => $aCols) {
            if ($sSubObject === $sTable) {
                return $aCols;
            }
        }

        return null;
    }

    /**
     * Generates a where clause for a sql statement
     *
     * @param ParameterCollection $oParams
     * @param array               $aOpts
     * @param string              $strColPrefix
     * @param array               $aColumns
     *
     * @return string
     */
    protected function GenerateCriteria(
        ParameterCollection &$oParams,
        Array $aOpts,
        $strColPrefix = '',
        array $aColumns = null
    )
    {
        $this->where(true);
        $sql = '';
        $aColumns = $aColumns ?: static::COLUMNS;
        foreach ($aColumns as $name => $def) {
            if (isset($aOpts[$name])) {
                $type = $this->getParamTypeFromColType($aOpts[$name], $def);
                if ($type === DB_PARAM_NULL && $aOpts[$name] === null) {
                    $sql .= sprintf('%s %s%s IS NULL ',
                                    $this->where(),
                                    $strColPrefix,
                                    $name
                    );
                }
                elseif (is_array($aOpts[$name])) {
                    // TODO: Generate IN clause
                }
                else {
                    $sql .= sprintf('%s %s%s=:%s ',
                                    $this->where(),
                                    $strColPrefix,
                                    $name,
                                    $name
                    );
                    $oParams->Add(new Parameter($name, $aOpts[$name], $type));
                }
            }
        }

        if (isset($aOpts['group_by'])) {
            if (isset($aColumns[$aOpts['group_by']])) {
                strlen($strColPrefix) == 0 ?: $aOpts['group_by'] = "{$strColPrefix}{$aOpts['group_by']}";
                $sql .= "GROUP BY {$aOpts['group_by']} ";
            }
        }

        if (isset($aOpts['order_by'])) {
            $sOrderBy = '';
            if (!is_array($aOpts['order_by'])) {
                $aOpts['order_by'] = [$aOpts['order_by']];
            }
            foreach ($aOpts['order_by'] as $sOrder) {
                $sColumn = $sDirection = null;
                if (strpos($sOrder, ' ') !== false) {
                    list($sColumn, $sDirection) = explode(' ', $sOrder, 2);
                }
                else {
                    $sColumn = $sOrder;
                }
                $sDirection = strtoupper($sDirection) ?: 'ASC';
                if (isset($aColumns[$sColumn]) &&
                    ($sDirection === 'ASC' || $sDirection === 'DESC')
                ) {
                    strlen($sOrderBy) == 0 ?: $sOrderBy .= ', ';
                    $sOrderBy .= "{$strColPrefix}$sColumn $sDirection";
                }
            }
            strlen($sOrderBy) == 0 ?: $sql .= "ORDER BY $sOrderBy ";
        }

        if (isset($aOpts['rows'])) {
            $sql .= 'LIMIT :rows ';
            $oParams->Add(new Parameter('rows', (int) $aOpts['rows'], DB_PARAM_INT));
        }

        if (isset($aOpts['skip'])) {
            $sql .= 'OFFSET :skip ';
            $oParams->Add(new Parameter('skip', (int) $aOpts['skip'], DB_PARAM_INT));
        }

        return $sql;
    }

    /**
     * Returns either WHERE or AND for sql
     * First call after resetting returns WHERE, the rest returns AND
     *
     * @param bool $bReset True to return where on next call
     *
     * @return void|string
     */
    private function where($bReset = false)
    {
        static $bWhere;
        if ($bReset) {
            $bWhere = false;

            return null;
        }
        if ($bWhere === false) {
            $bWhere = true;

            return 'WHERE ';
        }

        return 'AND ';
    }

    /**
     * Gets a DB_PARAM_* constant based on the COL_TYPE
     *
     * @param $val
     * @param $def
     *
     * @return null|string
     * @throws \UnexpectedValueException
     */
    private function getParamTypeFromColType($val, $def)
    {
        $return = null;
        if (isset($def['nullable']) && $def['nullable'] && is_bool($val)) {
            $return = DB_PARAM_NULL;
        }
        elseif (isset($def[COL_TYPE])) {
            if ($def[COL_TYPE] == COL_TYPE_BOOLEAN) {
                $return = DB_PARAM_BOOL;
            }
            elseif ($def[COL_TYPE] == COL_TYPE_INTEGER) {
                $return = DB_PARAM_INT;
            }
            elseif ($def[COL_TYPE] == COL_TYPE_DECIMAL) {
                $return = DB_PARAM_DEC;
            }
            else {
                $return = DB_PARAM_STR;
            }
        }
        if ($return === null) {
            throw new \UnexpectedValueException('Unknown column definition');
        }

        return $return;
    }

    /**
     * Inserts a new row into the root object, or a sub-object as specified
     *
     * @param $oObj
     * @param $sTable
     *
     * @return Retval
     */
    public function Insert(&$oObj, $sTable = null)
    {
        $aCols = $this->getColsFromTable($sTable);
        if (!is_array($aCols)) {
            // Uh oh, object name provided is not valid, return an error
            $oRet = new Retval;
            $oRet->AddError(sprintf(
                                'Unrecognized table provided to ' . get_called_class() . '::' . __FUNCTION__ . "($sTable)"
                            ));
        }
        else {
            // Try to validate the given object
            $oRet = $this->Validate($oObj, $aCols);
        }
        if (!$oRet->HasFailure()) {
            $oParams = new ParameterCollection();
            $strSql = $this->GenerateInsert($oParams, $oObj, $sTable, $aCols);
            try {
                $oRet->Set(
                    $this->DB
                        ->Prepare($strSql)
                        ->BindParams($oParams)
                        ->Execute()
                );
            } catch (DataException $e) {
                if ($e->getCode() == self::UNIQUE_CONSTRAINT_VIOLATION) {
                    $oRet->AddError('Unable to insert, ' . $e->getMessage(), $e);
                }
                else {
                    $oRet->AddError('Unable to query database', $e);
                }
            }
        }
        if (!$oRet->HasFailure()) {
            $strSql = 'SELECT LAST_INSERT_ID()';
            try {
                $oRet->Set(
                    $this->DB
                        ->Prepare($strSql)
                        ->Execute()
                        ->GetScalar()
                );
            } catch (DataException $e) {
                $oRet->AddError('Unable to query database', $e);
            }
        }
        if (!$oRet->HasFailure()) {
            $sIdField = 'id';
            foreach ($aCols as $sColName => $aCol) {
                if ($aCol[KEY_TYPE] == KEY_TYPE_PRIMARY) {
                    $sIdField = $sColName;
                    break;
                }
            }
            $oObj->$sIdField = $oRet->Get();
        }

        return $oRet;
    }

    /**
     * Validates given array of values against the table definition
     *
     * @param object $oObj
     * @param array  $aColumns
     *
     * @return Retval
     * @throw RuntimeException
     */
    public function Validate(
        $oObj,
        array $aColumns = null
    )
    {
        $aVars = (array) $oObj;
        $oRetval = new Retval();
        foreach ($aColumns ?: static::COLUMNS as $strCol => $aCol) {
            if (isset($aCol[DB_REQUIRED]) &&
                $aCol[DB_REQUIRED] === true &&
                !isset($aVars[$strCol])
            ) {
                $oRetval->AddError($strCol . ' is required.');
            }

            if (isset($aCol[COL_LENGTH]) &&
                isset($aVars[$strCol]) &&
                strlen($aVars[$strCol]) > $aCol[COL_LENGTH]
            ) {
                $oRetval->AddError($strCol . ' is longer than max length (' . $aCol[COL_LENGTH] . ').');
            }

            if (isset($aCol[COL_TYPE]) &&
                isset($aVars[$strCol])
            ) {
                switch ($aCol[COL_TYPE]) {
                    case COL_TYPE_INTEGER:
                        if (!is_numeric($aVars[$strCol]) ||
                            (string) (int) $aVars[$strCol] !== (string) $aVars[$strCol]
                        ) {
                            $oRetval->AddError($strCol . ' must be a valid integer.');
                        }
                        break;
                    case COL_TYPE_BOOLEAN:
                        if (!is_bool($aVars) &&
                            (string) (bool) $aVars[$strCol] !== (string) $aVars[$strCol]
                        ) {
                            $oRetval->AddError($strCol . ' must be a valid boolean.');
                        }
                        break;
                    case COL_TYPE_DECIMAL:
                        if (!is_numeric($aVars[$strCol]) ||
                            !preg_match('/^[+\-]?(?:\d+(?:\.\d*)?|\.\d+)$/', trim($aVars[$strCol]))
                        ) {
                            $oRetval->AddError($strCol . ' must be a valid decimal.');
                        }
                        break;
                    case COL_TYPE_FIXED_STRING:
                        if (!isset($aCol[COL_LENGTH]) || !is_int($aCol[COL_LENGTH])) {
                            throw new \RuntimeException('COL_TYPE_FIXED_STRING found with no defined or invalid col_length!');
                        }
                        elseif (strlen($aVars[$strCol]) !== $aCol[COL_LENGTH]) {
                            $oRetval->AddError($strCol . ' must be fixed length (' . $aCol[COL_LENGTH] . ').');
                        }
                        break;
                }
            }

            if (isset($aCol[DB_VALIDATION]) &&
                isset($aVars[$strCol]) &&
                !preg_match($aCol[DB_VALIDATION], $aVars[$strCol])
            ) {
                $oRetval->AddError($strCol . ' did not validate.');
            }
        }

        return $oRetval;
    }

    /**
     * Generates an INSERT INTO...VALUES... statement based on table
     *   definition and given values to insert
     *   Always sets created and modified to current date and time
     *
     * @param ParameterCollection $oParams
     * @param object              $oObj
     * @param null                $strTable
     * @param array               $aColumns
     *
     * @return string
     */
    protected function GenerateInsert(
        ParameterCollection &$oParams,
        $oObj,
        $strTable = null,
        array $aColumns = null
    )
    {
        $aCols = [];
        $aVals = [];
        $aOpts = (array) $oObj;
        foreach ($aColumns ?: static::COLUMNS as $name => $def) {
            if (strtolower($name) == 'created' || strtolower($name) == 'modified') {
                $aCols[] = sprintf('`%s`', $name);
                $aVals[] = 'NOW()';
            }
            elseif (isset($aOpts[$name])) {
                $aCols[] = sprintf('`%s`', $name);
                $oParams->Add(new Parameter(
                                  $name,
                                  $aOpts[$name],
                                  $this->getParamTypeFromColType($aOpts[$name], $def)
                              ));
                $aVals[] = ':' . $name;
            }
        }

        return sprintf(
            'INSERT INTO `%s` (%s) VALUES (%s)',
            $strTable ?: static::TABLE_NAME,
            implode(',', $aCols),
            implode(',', $aVals)
        );
    }

    /**
     * Updates the root object by default, but can be passed
     *   the name of any sub-object to update that
     *
     * @param $oObj
     * @param $sTable
     *
     * @return Retval
     */
    public function Update(&$oObj, $sTable = null)
    {
        $aCols = $this->getColsFromTable($sTable);
        if (!is_array($aCols)) {
            // Uh oh, object name provided is not valid, return an error
            $oRet = new Retval;
            $oRet->AddError(sprintf(
                                'Unrecognized table provided to ' . get_called_class() . '::' . __FUNCTION__
                            ));
        }
        else {
            // Try to validate the given object
            $oRet = $this->Validate($oObj, $aCols);
        }
        if (!$oRet->HasFailure()) {
            $oParams = new ParameterCollection();
            $strSql = $this->GenerateUpdate($oParams, $oObj, $sTable, $aCols);
            try {
                $oRet->Set(
                    $this->DB
                        ->Prepare($strSql)
                        ->BindParams($oParams)
                        ->Execute()
                );
            } catch (DataException $e) {
                if ($e->getCode() == self::UNIQUE_CONSTRAINT_VIOLATION) {
                    $oRet->AddError('Unable to update, ' . $e->getMessage(), $e);
                }
                else {
                    $oRet->AddError('Unable to query database', $e);
                }
            }
        }

        return $oRet;
    }

    /**
     * Generates an update statement for the table definition and given options
     *   Adds a where clause on the primary key when provided
     *   Does not allow changing created datetime, and sets modified to current datetime
     *
     * @param ParameterCollection $oParams
     * @param array               $oObj
     * @param null                $strTable
     * @param array               $aColumns
     *
     * @return string
     */
    protected function GenerateUpdate(
        ParameterCollection &$oParams,
        $oObj,
        $strTable = null,
        array $aColumns = null
    )
    {
        $strRet = sprintf(
            'UPDATE `%s` SET ',
            $strTable ?: static::TABLE_NAME
        );
        $strIdField = '';
        $cIdType = null;
        $aOpts = (array) $oObj;
        foreach ($aColumns ?: static::COLUMNS as $name => $def) {
            if (strtolower($name) == 'created') {
                continue;
            }
            elseif (strtolower($name) == 'modified') {
                $strRet .= '`modified` = NOW(),';
            }
            elseif (isset($def[KEY_TYPE]) && $def[KEY_TYPE] === KEY_TYPE_PRIMARY) {
                $strIdField = $name;
                $cIdType = $this->getParamTypeFromColType($aOpts[$name], $def);
            }
            elseif (isset($aOpts[$name])) {
                $type = $this->getParamTypeFromColType($aOpts[$name], $def);
                $oParams->Add(new Parameter($name, $aOpts[$name], $type));
                $strRet .= sprintf("`%s` = :%s,", $name, $name);
            }
        }
        $strRet = substr($strRet, 0, -1) . ' ';
        if (strlen($strIdField) > 0 && isset($aOpts[$strIdField])) {
            $oParams->Add(new Parameter($strIdField, $aOpts[$strIdField], $cIdType));
            $strRet .= sprintf("WHERE `%s` = :%s ", $strIdField, $strIdField);
        }

        return $strRet;
    }

    public function VerifyModelDefinition()
    {
        $aRet = [];

        // Do the root
        $oRetval = $this->VerifyTableDefinition();
        if ($oRetval->HasFailure()) {
            // Fail fast if root fails
            return $oRetval;
        }
        else {
            $aRet[static::TABLE_NAME] = $oRetval->Get();
        }

        foreach (static::SUB_OBJECTS as $strTable => $aCols) {
            $oRetval = $this->VerifyTableDefinition($strTable, $aCols);
            if ($oRetval->HasFailure()) {
                $aRet[$strTable] = $oRetval->GetError();
            }
            else {
                $aRet[$strTable] = $oRetval->Get();
            }
        }

        $oRetval = new Retval();
        $oRetval->Set($aRet);

        return $oRetval;
    }

    /**
     * Verifies the current tables definition and updates if necessary
     *
     * @param null  $sTable
     * @param array $aColumns
     *
     * @return Retval
     */
    private function verifyTableDefinition(
        $sTable = null,
        array $aColumns = null
    )
    {
        $sTable = $sTable ?: static::TABLE_NAME;
        $aColumns = $aColumns ?: static::COLUMNS;

        $oRetval = $this->verifyTableExistence($sTable, $aColumns);
        if ($oRetval->HasFailure() || $oRetval->Get() == static::TABLE_CREATED) {
            return $oRetval;
        }
        $strSql = sprintf('DESCRIBE `%s`', $sTable);
        try {
            $oRetval->Set(
                $this->DB
                    ->Query($strSql)
                    ->GetAll()
            );
        } catch (DataException $e) {
            $oRetval->AddError('Unable to query database', $e);

            return $oRetval;
        }
        $aRet = [];
        $strUpdate = 'ALTER TABLE `' . $sTable . '` ';
        $aTableCols = array_map(function ($el) {
            return (array) $el;
        }, $oRetval->Get());
        $aTableCols = array_combine(
            array_column($aTableCols, 'Field'),
            array_values($aTableCols)
        );
        foreach ($aColumns as $strCol => $aCol) {
            $bColMissing = false;
            $bColWrong = false;
            if (!isset($aTableCols[$strCol])) {
                // Column is missing, add it
                $bColMissing = true;
                $aRet[] = "Adding column $strCol";
                $strUpdate .= "\nADD COLUMN ";
            }
            else {
                $aColMatch = $aTableCols[$strCol];
                switch ($aCol[COL_TYPE]) {
                    case COL_TYPE_DATETIME:
                        if ($aColMatch['Type'] !== 'datetime') {
                            $bColWrong = true;
                        }
                        break;
                    case COL_TYPE_DECIMAL:
                        if (isset($aCol[COL_LENGTH]) && isset($aCol['scale'])) {
                            $sType = sprintf('decimal(%d,%d)', $aCol[COL_LENGTH], $aCol['scale']);
                        }
                        else {
                            $sType = 'decimal(10,4)';
                        }
                        if ($aColMatch['Type'] !== $sType) {
                            $bColWrong = true;
                        }
                        break;
                    case COL_TYPE_TEXT:
                        if ($aColMatch['Type'] !== 'text') {
                            $bColWrong = true;
                        }
                        break;
                    case COL_TYPE_INTEGER:
                        $sType = 'int';
                        if (isset($aCol[COL_LENGTH]) && is_numeric($aCol[COL_LENGTH])) {
                            if ($aCol[COL_LENGTH] > 11) {
                                $sType = 'bigint';
                            }
                            $sType .= sprintf(
                                "(%d)",
                                $aCol[COL_LENGTH]
                            );
                        }
                        else {
                            $sType .= '(11)';
                        }
                        if ($aColMatch['Type'] !== $sType) {
                            $bColWrong = true;
                        }
                        break;
                    case COL_TYPE_BOOLEAN:
                        if ($aColMatch['Type'] !== 'tinyint(1)') {
                            $bColWrong = true;
                        }
                        break;
                    case COL_TYPE_STRING:
                        if (isset($aCol[COL_LENGTH]) && is_numeric($aCol[COL_LENGTH])) {
                            $sType = sprintf("varchar(%d)", $aCol[COL_LENGTH]);
                            if ($aColMatch['Type'] !== $sType) {
                                $bColWrong = true;
                            }
                        }
                        else {
                            throw new \UnexpectedValueException(
                                'Bad column definition. COL_TYPE_STRING requires col_length be set.');
                        }
                        break;
                    case COL_TYPE_FIXED_STRING:
                        if (isset($aCol[COL_LENGTH]) && is_numeric($aCol[COL_LENGTH])) {
                            $sType = sprintf("char(%d)", $aCol[COL_LENGTH]);
                            if ($aColMatch['Type'] !== $sType) {
                                $bColWrong = true;
                            }
                        }
                        else {
                            throw new \UnexpectedValueException(
                                'Bad column definition. COL_TYPE_STRING requires col_length be set.');
                        }
                        break;
                }

                if (isset($aCol[DB_EXTRA]) &&
                    is_array($aCol[DB_EXTRA])
                ) {
                    if (in_array(DB_NULLABLE, $aCol[DB_EXTRA])) {
                        if ($aColMatch['Null'] != 'YES') {
                            $bColWrong = true;
                        }
                    }
                    else {
                        if ($aColMatch['Null'] != 'NO') {
                            $bColWrong = true;
                        }
                    }

                    if (in_array(DB_AUTO_INCREMENT, $aCol[DB_EXTRA]) &&
                        !strpos($aColMatch['Extra'], 'auto_increment') > -1
                    ) {
                        $bColWrong = true;
                    }

                    $def = preg_grep('/^default.*$/i', $aCol[DB_EXTRA]);
                    if (count($def) > 0) {
                        if ($aColMatch['Default'] != $def[0]) {
                            $bColWrong = true;
                        }
                    }
                }
                else {
                    if ($aColMatch['Null'] != 'NO') {
                        $bColWrong = true;
                    }
                }

                if (isset($aCol[KEY_TYPE])) {
                    switch ($aCol[KEY_TYPE]) {
                        case KEY_TYPE_PRIMARY:
                            if ($aColMatch['Key'] != 'PRI') {
                                $bColWrong = true;
                            }
                            break;
                        case KEY_TYPE_UNIQUE:
                            if ($aColMatch['Key'] != 'UNI') {
                                $bColWrong = true;
                            }
                            break;
                        case KEY_TYPE_FOREIGN:
                            if ($aColMatch['Key'] != 'MUL') {
                                $bColWrong = true;
                            }
                            break;
                    }
                }

                if ($bColWrong) {
                    $aRet[] = "Updating column $strCol";
                    $strUpdate .= "\nCHANGE COLUMN ";
                }
            }
            if ($bColWrong || $bColMissing) {
                if ($bColWrong) {
                    $strUpdate .= "`$strCol` ";
                }
                $strUpdate .= $this->getColumnSqlFromDefinition($strCol, $aCol) . ",";
            }
            unset($strCol, $aCol);
        }
        $strUpdate = substr($strUpdate, 0, -1);
        if (count($aRet) > 0) {
            try {
                $oRetval->Set('Updating ' . $sTable);
                $this->DB->Query($strUpdate);
                $aRet[] = $sTable . ' has been updated';
                $oRetval->Set($aRet);
            } catch (\Exception $e) {
                $oRetval->AddError('Error updating table ' . $sTable, $e);
            }
        }
        else {
            $oRetval->Set($sTable . ' is up to date');
        }

        return $oRetval;
    }

    /**
     * @param null       $sTable
     * @param array|null $aColumns
     *
     * @return Retval
     */
    private function verifyTableExistence(
        $sTable = null,
        array $aColumns = null
    )
    {
        $oRetval = new Retval();
        $strSql = sprintf("SHOW TABLES LIKE '%s'", $sTable ?: static::TABLE_NAME);
        try {
            $oRetval->Set(
                $this->DB
                    ->Query($strSql)
                    ->GetAll()
            );
        } catch (DataException $e) {
            $oRetval->AddError('Unable to query database', $e);

            return $oRetval;
        }
        if (count($oRetval->Get()) == 0) {
            $oRetval = new Retval();
            $strSql = $this->GenerateCreateTable($sTable, $aColumns);
            try {
                $oRetval->Set($this->DB->Query($strSql));
            } catch (DataException $e) {
                $oRetval->AddError('Unable to query database', $e);
            }
            if (!$oRetval->HasFailure()) {
                $oRetval = new Retval();
                $oRetval->Set(static::TABLE_CREATED);
            }
        }

        return $oRetval;
    }

    /**
     * Generates the SQL to create table defined by class properties
     *
     * @param null  $sTable
     * @param array $aColumns
     *
     * @return null|string
     * @throws \UnexpectedValueException
     */
    protected function GenerateCreateTable(
        $sTable = null,
        array $aColumns = null
    )
    {
        if (strlen($sTable ?: static::TABLE_NAME) == 0
            || count($aColumns ?: static::COLUMNS) == 0
        ) {
            return null;
        }

        $aCols = [];
        foreach ($aColumns ?: static::COLUMNS as $strCol => $aCol) {
            $aCols[] = $this->getColumnSqlFromDefinition($strCol, $aCol);
        }

        return sprintf(
            "CREATE TABLE IF NOT EXISTS `%s` (%s\n) Engine=InnoDB",
            $sTable ?: static::TABLE_NAME,
            implode(',', $aCols)
        );
    }

    /**
     * @param $sCol
     * @param $aCol
     *
     * @return string
     */
    private function getColumnSqlFromDefinition($sCol, $aCol)
    {
        $sType = '';
        $sKey = '';
        $sExtra = '';
        switch ($aCol[COL_TYPE]) {
            case COL_TYPE_BOOLEAN:
                $sType = 'TINYINT(1)';
                break;
            case COL_TYPE_DATETIME;
                $sType = 'DATETIME';
                break;
            case COL_TYPE_DECIMAL:
                if (isset($aCol[COL_LENGTH]) && isset($aCol['scale'])) {
                    $sType = sprintf('DECIMAL(%d, %d)', $aCol[COL_LENGTH], $aCol['scale']);
                }
                else {
                    $sType = 'DECIMAL(10, 4)';
                }
                break;
            case COL_TYPE_INTEGER:
                $sType = 'INT';
                if (isset($aCol[COL_LENGTH]) && is_numeric($aCol[COL_LENGTH])) {
                    if ($aCol[COL_LENGTH] > 11) {
                        $sType = 'BIGINT';
                    }
                    $sType .= sprintf("(%d)", $aCol[COL_LENGTH]);
                }
                break;
            case COL_TYPE_TEXT:
                $sType = 'TEXT';
                break;
            case COL_TYPE_STRING:
                if (isset($aCol[COL_LENGTH]) && is_numeric($aCol[COL_LENGTH])) {
                    $sType = sprintf("VARCHAR(%d)", $aCol[COL_LENGTH]);
                }
                else {
                    throw new \UnexpectedValueException(
                        'Bad column definition. COL_TYPE_STRING requires col_length be set.');
                }
                break;
            case COL_TYPE_FIXED_STRING:
                if (isset($aCol[COL_LENGTH]) && is_numeric($aCol[COL_LENGTH])) {
                    $sType = sprintf("CHAR(%d)", $aCol[COL_LENGTH]);
                }
                else {
                    throw new \UnexpectedValueException(
                        'Bad column definition. COL_TYPE_FIXED_STRING requires col_length be set.');
                }
                break;
        }
        if (isset($aCol[DB_EXTRA]) &&
            is_array($aCol[DB_EXTRA])
        ) {
            if (in_array(DB_NULLABLE, $aCol[DB_EXTRA])) {
                $sExtra .= 'NULL ';
            }
            else {
                $sExtra .= 'NOT NULL ';
            }

            if (in_array(DB_AUTO_INCREMENT, $aCol[DB_EXTRA])) {
                $sExtra .= 'auto_increment ';
            }
            $def = preg_grep('/^default.*$/i', $aCol[DB_EXTRA]);
            if (count($def) > 0) {
                $sExtra .= $def[0];
            }
        }
        else {
            $sExtra .= 'NOT NULL ';
        }

        if (isset($aCol[KEY_TYPE])) {
            switch ($aCol[KEY_TYPE]) {
                case KEY_TYPE_PRIMARY:
                    $sKey = 'PRIMARY KEY';
                    break;
                case KEY_TYPE_UNIQUE:
                    $sKey = 'UNIQUE';
                    break;
                case KEY_TYPE_FOREIGN:
                    if (isset($aCol[FOREIGN_TABLE]) &&
                        isset($aCol[FOREIGN_COLUMN])
                    ) {
                        $sKey = sprintf(
                            ",\n\t\tFOREIGN KEY %s_%s (%s)\n\t\t\tREFERENCES `%s` (%s)",
                            $aCol[FOREIGN_TABLE],
                            $aCol[FOREIGN_COLUMN],
                            $sCol,
                            $aCol[FOREIGN_TABLE],
                            $aCol[FOREIGN_COLUMN]
                        );
                    }
            }
        }

        return sprintf(
            "\n\t`%s` %s %s %s",
            $sCol,
            $sType,
            $sExtra,
            $sKey
        );
    }
}
