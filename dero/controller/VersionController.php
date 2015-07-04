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
    const RE_CLASS = '/class\s+(\w+)\s+extends\s+basemodel/i';
    const RE_NAMESPACE = '/namespace\s+([\w\\\\]+)\s*;/i';
    const SH_GREP_CLASS = 'grep -ir -E "class \w+ extends BaseModel" '.ROOT.'/*';
    const SH_GREP_NS_PATTERN = 'grep -iE ^namespace.*$ %s';

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
        exec(self::SH_GREP_CLASS, $aOutput, $iRet);
        if( $iRet == 0 )
        {
            foreach($aOutput as $strOut)
            {
                if( preg_match('/^[A-Z]:/i', $strOut) )
                {
                    list($cDrive, $strFile, $strClassDefinition) = explode(':', $strOut);
                    $strFile = $cDrive .':'. $strFile;
                }
                else
                {
                    list($strFile, $strClassDefinition) = explode(':', $strOut);
                }
                $strClass = preg_replace(self::RE_CLASS,'\1', $strClassDefinition);
                $strMatch = exec(sprintf(self::SH_GREP_NS_PATTERN, $strFile));
                if( strlen($strMatch) > 0 && preg_match(self::RE_NAMESPACE, $strMatch) )
                {
                    $strMatch = preg_replace(self::RE_NAMESPACE,'\1',$strMatch);
                    $strClass = sprintf('%s\%s', $strMatch, $strClass);
                }
                $aRet[$strClass] = realpath($strFile);
            }
        }
        else
        {
            die("grep failed for classes extending base model\n");
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