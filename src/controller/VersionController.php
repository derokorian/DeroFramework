<?php

namespace Dero\Controller;

use Dero\Core\BaseController;
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
    const SH_GREP_CLASS = 'grep -ir -E "class \w+ extends %s" ' . ROOT . '/*';
    const SH_GREP_NS_PATTERN = 'grep -iE ^namespace.*$ %s';

    public function __construct()
    {
        if (PHP_SAPI !== 'cli') {
            header('Location: ' . $_SERVER[''] . '/error/404');
            exit;
        }
    }

    private function getModels($sExtends = 'BaseModel')
    {
        $aRet = [];
        exec(sprintf(self::SH_GREP_CLASS, $sExtends), $aOutput, $iRet);
        if ($iRet < 2) {
            foreach ($aOutput as $strOut) {
                if (preg_match('/^[A-Z]:/i', $strOut)) {
                    list($cDrive, $strFile, $strClassDefinition) = explode(':', $strOut);
                    $strFile = $cDrive . ':' . $strFile;
                }
                else {
                    list($strFile, $strClassDefinition) = explode(':', $strOut);
                }
                $strClass = preg_replace(sprintf(self::RE_CLASS, $sExtends), '\1', $strClassDefinition);
                $strMatch = exec(sprintf(self::SH_GREP_NS_PATTERN, $strFile));
                if (strlen($strMatch) > 0 && preg_match(self::RE_NAMESPACE, $strMatch)) {
                    $strMatch = preg_replace(self::RE_NAMESPACE, '\1', $strMatch);
                    $strClass = sprintf('%s\%s', $strMatch, $strClass);
                }
                $aRet[$strClass] = realpath($strFile);
                $aRet = array_merge($aRet, $this->getModels(end(explode('\\', $strClass))));
            }
        }
        else {
            die("grep returned $iRet searching for classes extending $sExtends\n");
        }

        return $aRet;
    }

    public function upgrade()
    {
        $db = Factory::GetDataInterface('default');

        try {
            $aModels = $this->getModels();
            foreach ($aModels as $strModel => $strFile) {
                Timing::start($strModel);
                require_once $strFile;
                /** @var \Dero\Data\BaseModel $oModel */
                $oModel = new $strModel($db);
                $oRet = $oModel->VerifyModelDefinition();
                if ($oRet->HasFailure()) {
                    echo "Error on $strModel table\n";
                    var_dump($oRet);

                    return;
                }
                unset($oModel, $oRet);
                Timing::end($strModel);
            }
            echo "Tables updated successfully.\n";
        } catch (\Exception $e) {
            echo "Problem creating tables.\n";
            var_dump($e);
        }

        try {
            $aDataPaths = Config::GetValue('application', 'paths', 'data_files');
            if (!empty($aDataPaths)) {
                $aDataPaths = (array) $aDataPaths;
                $aData = [];
                foreach ($aDataPaths as $sDataPath) {
                    $sDataPath = ROOT . DS . $sDataPath . DS;
                    if (is_readable($sDataPath)) {
                        $aDataFiles = glob($sDataPath . '*');
                        foreach ($aDataFiles as $sDataFile) {
                            $mData = json_decode(file_get_contents($sDataFile), true);
                            $aData = array_merge($aData, (array) $mData);
                        }
                    }
                }
                foreach ($aData as $aTableData) {
                    $sSelect = sprintf(
                        'SELECT * FROM `%s` WHERE %s',
                        $aTableData['target'],
                        $this->buildDataWhere($aTableData['identifiers'], $aTableData['data'])
                    );
                    $aExistingData = $db->Query($sSelect)
                                        ->GetAll();
                    $aDataKeys = array_keys($aTableData['data'][0]);
                    $hInsert = $db->Prepare(sprintf(
                                                'INSERT INTO `%s` (`%s`,`%s`) VALUES (:%s,%s)',
                                                $aTableData['target'],
                                                implode('`,`', $aDataKeys),
                                                implode('`,`', $aTableData['timestamps']),
                                                implode(',:', $aDataKeys),
                                                implode(',', array_fill(0, count($aTableData['timestamps']), 'NOW()'))
                                            ));
                    foreach ($aTableData['data'] as $aRow) {
                        $bRowExists = false;
                        foreach ($aExistingData as $mRow) {
                            $mRow = (array) $mRow;
                            $bIsThisRow = true;
                            foreach ($aDataKeys as $sKey) {
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
                        if (!$bRowExists) {
                            foreach ($aDataKeys as $sKey) {
                                $hInsert->BindParam(new Parameter($sKey, $aRow[$sKey]));
                            }
                            $hInsert->Execute();
                            echo "Successfully added " . implode(',', $aRow) . " to $aTableData[target]\n";

                        }
                    }
                }
            }
        } catch (\Exception $e) {
            echo "Problem loading data.\n";
            var_dump($e);
        }
    }

    private function buildDataWhere($aIdentifiers, $aData)
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