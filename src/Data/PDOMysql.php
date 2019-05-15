<?php

namespace Dero\Data;

use Dero\Core\Config;
use Exception;
use InvalidArgumentException;
use PDO;
use PDOException;
use PDOStatement;
use StdClass;
use UnexpectedValueException;

/**
 * PDO wrapper for MySQL
 *
 * @author    Ryan Pallas
 * @package   DeroFramework
 * @namespace Dero\Data
 * @see       DataInterface
 */
class PDOMysql implements DataInterface
{
    /**
     * @var PDOStatement
     */
    protected $oPDOStatement;
    protected $sInstance;
    protected $oInstance;

    public function __construct(string $Instance)
    {
        if (empty($Instance)) {
            throw new InvalidArgumentException(__CLASS__ . ' requires a non-empty instance to be specified');
        }
        else {
            $this->sInstance = $Instance;
        }

        if (!extension_loaded('pdo_mysql')) {
            throw new Exception('PDO_MySQL is not loaded - please check the server\'s configuration');
        }
    }

    /**
     * (non-PHPdoc)
     *
     * @param string $Query
     *
     * @return DataInterface allows method chaining
     * @throws DataException
     */
    public function Query(string $Query): DataInterface
    {
        $Query = preg_replace(["/[\r\n]+/", '/\s+/'], " ", $Query);
        static::LogQuery($Query);
        $oCon = $this->OpenConnection(substr(trim($Query), 0, 6) == 'SELECT');
        try {
            $this->oPDOStatement = $oCon->query($Query);
            if ($this->oPDOStatement === false) {
                $e = $oCon->errorInfo();
                throw new DataException('Error running query in ' . __CLASS__ . '::'
                                        . __FUNCTION__ . '(' . $e[2] . ')');
            }

            return $this;
        } catch (PDOException $e) {
            throw new DataException('Error running query in ' . __CLASS__ . '::' . __FUNCTION__, 0, $e);
        }
    }

    /**
     * Logs query to file or headers during debug mode
     *
     * @param string $strQuery
     */
    protected static function LogQuery(string $strQuery)
    {
        static $i = 0;
        if (!IS_DEBUG) {
            return;
        }

        if (PHP_SAPI == 'cli') {
            file_put_contents(
                '/tmp/query-' . date('ymdhm') . '.log',
                sprintf("query(%d): %s\n", $i++, $strQuery),
                FILE_APPEND
            );
        }
        else {
            header(sprintf('X-Query-%d: %s', $i++, $strQuery));
        }
    }

    /**
     * Opens a connection for a query
     *
     * @param bool $bIsRead
     *
     * @return PDO
     * @throws UnexpectedValueException
     * @throws DataException
     */
    protected function OpenConnection(bool $bIsRead)
    {
        if ($this->oPDOStatement) {
            unset($this->oPDOStatement);
        }
        $sType = null;

        if (!is_null(Config::getValue('database', $this->sInstance, 'write')) &&
            !is_null(Config::getValue('database', $this->sInstance, 'read'))
        ) {
            if ($bIsRead) {
                if ($this->oInstance['read']) {
                    return $this->oInstance['read'];
                }
                $sType = 'read';
            }
            else {
                if ($this->oInstance['write']) {
                    return $this->oInstance['write'];
                }
                $sType = 'write';
            }
        }
        elseif (!is_null(Config::getValue('database', $this->sInstance, 'read'))) {
            if ($this->oInstance['read']) {
                return $this->oInstance['read'];
            }
            $sType = 'read';
        }
        elseif (!is_null(Config::getValue('database', $this->sInstance, 'write'))) {
            if ($this->oInstance['write']) {
                return $this->oInstance['write'];
            }
            $sType = 'write';
        }
        elseif (!is_null(Config::getValue('database', $this->sInstance, 'name'))) {
            if (isset($this->oInstance)) {
                return $this->oInstance;
            }
        }
        else {
            throw new UnexpectedValueException('Database connection information not properly defined');
        }

        if (is_null($sType)) {
            $aOpts['Name'] = Config::getValue('database', $this->sInstance, 'name');
            $aOpts['Host'] = Config::getValue('database', $this->sInstance, 'host');
            $aOpts['User'] = Config::getValue('database', $this->sInstance, 'user');
            $aOpts['Pass'] = Config::getValue('database', $this->sInstance, 'pass');
            $aOpts['Port'] = Config::getValue('database', $this->sInstance, 'port') ?: 3306;
            if (in_array(null, $aOpts)) {
                throw new UnexpectedValueException('Database connection information not properly defined');
            }
            try {
                $this->oInstance = new PDO(
                    sprintf(
                        'mysql:dbname=%s;host=%s;port=%d',
                        $aOpts['Name'],
                        $aOpts['Host'],
                        $aOpts['Port']
                    ),
                    $aOpts['User'],
                    $aOpts['Pass']
                );

                return $this->oInstance;
            } catch (PDOException $e) {
                throw new DataException("Unable to open database connection\n" . var_export($aOpts, true), 0, $e);
            }
        }
        else {
            $aOpts['Name'] = Config::getValue('database', $this->sInstance, $sType, 'name');
            $aOpts['Host'] = Config::getValue('database', $this->sInstance, $sType, 'host');
            $aOpts['User'] = Config::getValue('database', $this->sInstance, $sType, 'user');
            $aOpts['Pass'] = Config::getValue('database', $this->sInstance, $sType, 'pass');
            $aOpts['Port'] = Config::getValue('database', $this->sInstance, $sType, 'port') ?: 3306;
            if (in_array(null, $aOpts)) {
                throw new UnexpectedValueException('Database connection information not properly defined');
            }
            try {
                $this->oInstance[$sType] = new PDO(
                    sprintf(
                        'mysql:dbname=%s;host=%s;port=%d',
                        $aOpts['Name'],
                        $aOpts['Host'],
                        $aOpts['Port']
                    ),
                    $aOpts['User'],
                    $aOpts['Pass']
                );

                return $this->oInstance[$sType];
            } catch (PDOException $e) {
                throw new DataException("Unable to open database connection\n" . var_export($aOpts, true), 0, $e);
            }
        }
    }

    /**
     * (non-PHPdoc)
     *
     * @param string $Query
     *
     * @return DataInterface allows method chaining
     * @throws DataException
     */
    public function Prepare(string $Query): DataInterface
    {
        $Query = preg_replace(["/[\r\n]+/", '/\s+/'], " ", $Query);
        static::LogQuery($Query);
        $oCon = $this->OpenConnection(substr(trim($Query), 0, 6) == 'SELECT');
        try {
            $this->oPDOStatement = $oCon->prepare($Query);
            if ($this->oPDOStatement === false) {
                $e = $oCon->errorInfo();
                throw new DataException('Error preparing query in ' . __CLASS__ . '::'
                                        . __FUNCTION__ . '(' . $e[2] . ')');
            }

            return $this;
        } catch (PDOException $e) {
            throw new DataException('Error preparing query in ' . __CLASS__ . '::' . __FUNCTION__, 0, $e);
        }

    }

    /**
     * (non-PHPdoc)
     *
     * @param ParameterCollection $Params
     *
     * @return DataInterface allows method chaining
     * @throws DataException
     * @see DataInterface::BindParams()
     *
     */
    public function BindParams(ParameterCollection $Params): DataInterface
    {
        foreach ($Params as $Param) {
            if ($Param instanceof Parameter) {
                $this->BindParam($Param);
            }
        }

        return $this;
    }

    /**
     * (non-PHPdoc)
     *
     * @param Parameter $Param
     *
     * @return DataInterface allows method chaining
     * @throws DataException
     * @see DataInterface::BindParam()
     *
     */
    public function BindParam(Parameter $Param): DataInterface
    {
        if (!$this->oPDOStatement) {
            return $this;
        }
        try {
            $this->oPDOStatement->bindValue($Param->Name, $Param->Value, $Param->Type);

            return $this;
        } catch (Exception $e) {
            throw new DataException('unable to bind parameter ' . $e->getMessage());
        }
    }

    /**
     * (non-PHPdoc)
     *
     * @return DataInterface allows method chaining
     * @throws DataException
     * @see DataInterface::Execute()
     */
    public function Execute(): DataInterface
    {
        if (!$this->oPDOStatement) {
            return $this;
        }
        try {
            if (!$this->oPDOStatement->execute()) {
                $aErr = $this->oPDOStatement->errorInfo();
                switch ($this->oPDOStatement->errorCode()) {
                    case 23000:
                        throw new DataException($aErr[2], $aErr[1]);
                }
            }

            return $this;
        } catch (PDOException $e) {
            throw new DataException('Error executing query in ' . __CLASS__ . '::' . __FUNCTION__, 0, $e);
        }
    }

    /**
     * (non-PHPdoc)
     *
     * @param $mClass
     *
     * @return bool|StdClass object with properties mapped to selected columns
     * @see DataInterface::GetRow()
     *
     */
    public function Get($mClass = null)
    {
        if (!$this->oPDOStatement) {
            return false;
        }

        if (is_object($mClass)) {
            return $this->oPDOStatement->fetch(PDO::FETCH_INTO, $mClass);
        }

        if (is_string($mClass) && class_exists($mClass)) {
            return $this->oPDOStatement->fetch(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, $mClass);
        }

        return $this->oPDOStatement->fetch(PDO::FETCH_OBJ);
    }

    /**
     * (non-PHPdoc)
     *
     * @param $strClass
     *
     * @return bool|array(StandardObject) array of objects with properties mapped to selected columns
     * @see DataInterface::GetAll()
     *
     */
    public function GetAll($strClass = null)
    {
        if (!$this->oPDOStatement) {
            return false;
        }

        return is_null($strClass) || !class_exists($strClass)
            ? $this->oPDOStatement->fetchAll(PDO::FETCH_OBJ)
            : $this->oPDOStatement->fetchAll(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, $strClass);
    }

    /**
     * (non-PHPdoc)
     *
     * @return mixed
     * @see DataInterface::GetScalar()
     */
    public function GetScalar()
    {
        if (!$this->oPDOStatement) {
            return false;
        }

        return $this->oPDOStatement->fetchColumn(0);
    }

    /**
     * (non-PHPdoc)
     *
     * @return mixed
     * @see DataInterface::RowCount()
     */
    public function RowCount()
    {
        if (!$this->oPDOStatement) {
            return false;
        }

        return $this->oPDOStatement->rowCount();
    }
}

