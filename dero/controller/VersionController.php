<?php

namespace Dero\Controller;
use Dero\Data\Factory;
use Dero\Core\BaseController;
use Dero\Core\Timing;

/**
 * Version controller
 * @author Ryan Pallas
 * @package DeroFramework
 * @namespace Dero\Controller
 * @since 2014-10-08
 */

class VersionController extends BaseController
{
    public function __construct() {
        if( PHP_SAPI !== 'cli' )
        {
            header('Location: ' . $_SERVER[''] . '/error/404');
            exit;
        }
    }

    private function getModels()
    {
        $aRet = [];
        $strGrep = 'grep -ir -E "class \w+ extends BaseModel" '.ROOT.'/*';
        exec($strGrep, $aOutput, $iRet);
        if($iRet == 0)
        {
            $strGrep = 'grep -iE ^namespace.*$ %s';
            foreach($aOutput as $strOut)
            {
                if(preg_match('/[A-Z]:/', $strOut))
                {
                    list($cDrive, $strFile, $strClassDefinition) = explode(':', $strOut);
                    $strFile = $cDrive .':'. $strFile;
                }
                else
                {
                    list($strFile, $strClassDefinition) = explode(':', $strOut);
                }
                $strClass = str_replace(array('class ', ' extends BaseModel'), null, trim($strClassDefinition));
                $strMatch = exec(sprintf($strGrep, $strFile));
                if($iRet == 0)
                {
                    $strMatch = str_replace(array('namespace ', ';'), null, trim($strMatch));
                    $strClass = sprintf('%s\%s', $strMatch, $strClass);
                }
                $aRet[$strClass] = realpath($strFile);
            }
        }
        else
        {
            die("grep failed for classes extending base model\n$strGrep\n");
        }
        return $aRet;
    }

    public function upgrade()
    {
        $db = Factory::GetDataInterface('default');

        try {
            $aModels = $this->getModels();
            foreach($aModels as $strModel => $strFile)
            {
                Timing::start($strModel);
                require_once $strFile;
                /** @var \Dero\Data\BaseModel $oModel */
                $oModel = new $strModel($db);
                $oRet = $oModel->VerifyTableDefinition();
                if( $oRet->HasFailure() )
                {
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
    }
}