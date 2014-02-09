<?php

namespace App\Controller;
use App\Model\BlogModel;
use App\Model\UserModel;
use Dero\Data\Factory;
/**
 * Blog controller
 * @author Ryan Pallas
 * @package SampleSite
 * @namespace App\Controller
 * @since 2013-12-06
 */

class InstallController extends \Dero\Core\BaseController
{
    public function __construct() { }

    public function index()
    {
        if( PHP_SAPI === 'cli' )
        {
            $db =Factory::GetDataInterface('default');
            $oBlogModel = new BlogModel($db);
            $oUserModel = new UserModel($db);
            echo $oUserModel->GenerateCreateTable();
            echo PHP_EOL . PHP_EOL;
            echo $oBlogModel->GenerateCreateTable();
            echo PHP_EOL . PHP_EOL;
            echo "Would you likst to execute these create statements [y/n]?\n";
            $f = fopen("php://stdin", 'r');
            $a = fgets($f);
            if( strtolower($a) )
            {
                echo "Creating tables...\n";
                try
                {
                    $oRet = $oUserModel->CreateTable();
                    if( $oRet->HasFailure() )
                    {
                        echo "Error on user table\n";
                        var_dump($oRet);
                        return;
                    }

                    $oRet = $oBlogModel->CreateTable();
                    if( $oRet->HasFailure() )
                    {
                        echo "Error on blog-post table\n";
                        var_dump($oRet);
                        return;
                    }

                    if( !$oRet->HasFailure() )
                    {
                        echo "Tables created successfully.\n";
                    }
                } catch (\Exception $e) {
                    echo "Problem creating tables.\n";
                    var_dump($e);
                }

            }
        }
        else
        {
            var_dump(PHP_SAPI);
        }
    }
}