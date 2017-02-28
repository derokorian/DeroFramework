<?php

namespace Dero\Controller;

use Dero\Core\Config;
use Dero\Core\Timing;
use Dero\Data\Factory;
use Dero\Data\Parameter;

/**
 * Version controller
 *
 * @author    Ryan Pallas
 * @package   DeroFramework
 * @namespace Dero\Controller
 * @since     2014-10-08
 */
class VersionController extends BaseController
{
    const RE_CLASS = '/class\s+(\w+)\s+extends\s+%s/i';
    const RE_NAMESPACE = '/namespace\s+([\w\\\\]+)\s*;/i';
    const RE_DEPENDENCIES = '/@depends ([a-z\\\\]+)/i';
    const SH_GREP_CLASS = 'grep -ir -E "class \w+ extends %s" ' . ROOT . '/*';
    const SH_GREP_NS_PATTERN = 'grep -iE ^namespace.*$ %s';

    /** @var \Dero\Data\DataInterface */
    private $db;

    /** @var string[] */
    private $aModels;

    public function __construct()
    {
        if (PHP_SAPI !== 'cli') {
            header('Location: ' . $_SERVER[''] . '/error/404');
            exit;
        }

        $this->db = Factory::GetDataInterface('default');
    }

    public function upgrade()
    {
        try {
            $this->verifyModels();
            $this->verifyTableData();
        } catch (\Exception $e) {
            // probably do something else here
            throw $e;
        }
    }

    private function setFileAndClass(string $sLine, string $sExtends, string &$sFile, string &$sClass)
    {
        if (preg_match('/^[A-Z]:/i', $sLine)) {
            list($cDrive, $sFile, $sClass) = explode(':', $sLine);
            $sFile = $cDrive . ':' . $sFile;
        }
        else {
            list($sFile, $sClass) = explode(':', $sLine);
        }

        $sClass = preg_replace(sprintf(self::RE_CLASS, $sExtends), '\1', $sClass);
        $sMatch = exec(sprintf(self::SH_GREP_NS_PATTERN, $sFile));
        if (strlen($sMatch) > 0 && preg_match(self::RE_NAMESPACE, $sMatch)) {
            $sMatch = preg_replace(self::RE_NAMESPACE, '\1', $sMatch);
            $sClass = sprintf('%s\%s', $sMatch, $sClass);
        }
    }

    private function getModels(string $sExtends = 'BaseModel') : array
    {
        $aRet = [];
        exec(sprintf(self::SH_GREP_CLASS, $sExtends), $aOutput, $iRet);
        if ($iRet < 2) {
            foreach ($aOutput as $strOut) {
                $strFile = $strClass = '';
                $this->setFileAndClass($strOut, $sExtends, $strFile, $strClass);

                $aRet[$strClass] = realpath($strFile);
                $t = explode('\\', $strClass);
                $aRet = array_merge($aRet, $this->getModels(end($t)));
            }
        }
        else {
            throw new \OutOfBoundsException("grep returned $iRet searching for classes extending $sExtends\n");
        }

        return $aRet;
    }

    private function verifyModels()
    {
        $this->aModels = $this->getModels();
        foreach ($this->aModels as $strModel => $strFile) {
            $this->runModel($strModel, $strFile);
        }
        echo "Tables updated successfully.\n";
    }

    private function getDependencies(string $sClass) : array
    {
        $oRefClass = new \ReflectionClass($sClass);
        $aDeps = [];

        $sDoc = $oRefClass->getDocComment();
        if (preg_match_all(self::RE_DEPENDENCIES, $sDoc, $aMatches)) {
            $aDeps = $aMatches[1];
        }

        return $aDeps;
    }

    private function runModel(string $sClass, string $sFile)
    {
        Timing::start($sClass);
        require_once $sFile;

        $aDepends = $this->getDependencies($sClass);

        foreach ($aDepends as $sDepend) {
            isset($this->aModels[$sDepend]) && $this->runModel($sDepend, $this->aModels[$sDepend]);
        }

        /** @var \Dero\Data\BaseModel $oModel */
        $oModel = new $sClass($this->db);
        $oRet = $oModel->VerifyModelDefinition();
        if ($oRet->hasFailure()) {
            $exceptions = $oRet->getException();
            if (!is_array($exceptions)) {
                $exceptions = [$exceptions];
            }
            echo "Error on $sClass table\n";
            echo implode("\n", (array) $oRet->getError()) . "\n";
            echo implode("\n", array_map(function ($e) {
                    return $e->getMessage();
                }, $exceptions)) . "\n";
            exit(1);
        }
        else {
            echo implode("\n", $oRet->get()) . "\n";
        }
        unset($oModel, $oRet);
        Timing::end($sClass);
    }

    private function verifyTableData()
    {
        foreach ($this->getTableData() as $aTableData) {
            $aExistingData = $this->getExistingData($aTableData);

            $aDataKeys = array_keys($aTableData['data'][0]);
            $this->prepareInsert($aTableData, $aDataKeys);

            foreach ($aTableData['data'] as $aRow) {
                if (!$this->checkIfDataExists($aTableData['identifiers'], $aExistingData, $aRow)) {
                    $this->executeInsert($aDataKeys, $aRow);
                    echo "Successfully added " . implode(',', $aRow) . " to $aTableData[target]\n";
                }
            }
        }
    }

    private function prepareInsert($aTableData, $aDataKeys)
    {
        $this->db->Prepare(sprintf(
                               'INSERT INTO `%s` (`%s`,`%s`) VALUES (:%s,%s)',
                               $aTableData['target'],
                               implode('`,`', $aDataKeys),
                               implode('`,`', $aTableData['timestamps']),
                               implode(',:', $aDataKeys),
                               implode(',', array_fill(0, count($aTableData['timestamps']), 'NOW()'))
                           ));
    }

    private function executeInsert($aDataKeys, $aRow)
    {
        foreach ($aDataKeys as $sKey) {
            $this->db->BindParam(new Parameter($sKey, $aRow[$sKey]));
        }
        $this->db->Execute();
    }

    private function getExistingData($aTableData)
    {
        $sSelect = sprintf(
            'SELECT * FROM `%s` WHERE %s',
            $aTableData['target'],
            $this->buildDataWhere($aTableData['identifiers'], $aTableData['data'])
        );

        return $this->db->Query($sSelect)
                        ->GetAll();
    }

    private function checkIfDataExists(array $aIdentifiers, array $aExistingData, array $aRow) : bool
    {
        $bRowExists = false;
        foreach ($aExistingData as $mRow) {
            $mRow = (array) $mRow;
            $bIsThisRow = true;
            foreach ($aIdentifiers as $sKey) {
                if (!isset($mRow[$sKey]) ||
                    $mRow[$sKey] != $aRow[$sKey]
                ) {
                    $bIsThisRow = false;
                    break;
                }
            }
            if ($bIsThisRow) {
                $bRowExists = true;
            }
        }

        return $bRowExists;
    }

    private function getTableData()
    {
        foreach ($this->getDataPaths() as $sDataPath) {
            if (is_readable($sDataPath)) {
                $aDataFiles = glob($sDataPath . '*');

                foreach ($aDataFiles as $sDataFile) {
                    $mData = $this->getDataFromFile($sDataFile);

                    foreach ($mData as $aDataRow) {
                        if ($this->validateTableData($aDataRow)) {
                            yield $aDataRow;
                        }
                    }
                }
            }
        }
    }

    private function getDataPaths()
    {
        foreach ((array) Config::getValue('application', 'paths', 'data_files') as $sPath) {
            yield ROOT . DS . $sPath . DS;
        }
    }

    private function getDataFromFile($sDataFile)
    {
        $mData = json_decode(file_get_contents($sDataFile), true);
        if (isset($mData['target'])) {
            $mData = [$mData];
        }

        return $mData;
    }

    private function validateTableData(array $aTableData) : bool
    {
        $bRetval = !empty($aTableData['target'])
                   && !empty($aTableData['identifiers'])
                   && isset($aTableData['timestamps']) // there may not be any timestamp columns
                   && !empty($aTableData['data']);

        if ($bRetval) {
            foreach ($aTableData['identifiers'] as $sIdentifier) {
                foreach ($aTableData['data'] as $aData) {
                    if (!isset($aData[$sIdentifier])) {
                        $bRetval = false;
                        break 2;
                    }
                }
            }
        }

        return $bRetval;
    }

    private function buildDataWhere(array $aIdentifiers, array $aData) : string
    {
        $aChecks = [];
        foreach ($aData as $aRow) {
            $sCheck = '';
            foreach ($aIdentifiers as $sIdentifier) {
                $sCheck .= sprintf(
                    '%s`%s` = "%s"',
                    strlen($sCheck) > 0 ? ' AND ' : '',
                    $sIdentifier,
                    $aRow[$sIdentifier]
                );
            }
            $aChecks[] = "($sCheck)";
        }

        return implode(' OR ', $aChecks);
    }
}
