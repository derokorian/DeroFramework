<?php

use Dero\Core\ResourceManager;

class ResourceManagerTest extends PHPUnit_Framework_TestCase
{
    public function testScript()
    {
        ResourceManager::addScript('angular');
        $strRet = ResourceManager::loadScripts();
        $this->assertNotEmpty($strRet);

        // loads a dependency
        $this->assertRegExp(
            '/<script.*jquery-\d+.\d+.\d+.min\.js.*\/script/',
            $strRet,
            'Failed loading script dependency'
        );
        // loads itself
        $this->assertRegExp(
            '/<script.*angular\.min\.js.*\/script/',
            $strRet,
            'Failed loading requested script'
        );
    }

    /**
     * @expectedException OutOfBoundsException
     */
    public function testScriptFail()
    {
        ResourceManager::addScript('non-existent');
    }

    public function testStyle()
    {
        // @deprecated no styles ship with framework, need to rewrite this
        //        ResourceManager::AddStyle('site');
        //        $strRet = ResourceManager::LoadStyles();
        //        $this->assertNotEmpty($strRet);
        //
        //        // loads its style tag
        //        $this->assertRegExp(
        //            '/<link.*site\.css.*\/>/',
        //            $strRet,
        //            'Failed loading css tag'
        //        );
    }

    /**
     * @expectedException OutOfBoundsException
     */
    public function testStyleFail()
    {
        ResourceManager::addStyle('non-existent');
    }
}
