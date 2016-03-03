<?php

use Dero\Core\Config;

class ConfigTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        file_put_contents(
            ROOT . DS . 'src/config/test.json',
            json_encode([
                            'user'    => 'myUser',
                            'pass'    => 'myPass',
                            'complex' => [
                                'configuration' => [
                                    'structure' => true,
                                ],
                            ],
                        ])
        );

        if (!file_exists(ROOT . DS . 'config')) {
            mkdir(ROOT . DS . 'config');
        }

        file_put_contents(
            ROOT . DS . 'config/test.json',
            json_encode([
                            'pass'   => 'testPass',
                            'custom' => 1234,
                        ])
        );
    }

    public function tearDown()
    {
        unlink(ROOT . DS . 'src/config/test.json');
        unlink(ROOT . DS . 'config/test.json');
        rmdir(ROOT . DS . 'config');
    }

    public function testSimple()
    {
        $this->assertEquals(
            'myUser',
            Config::getValue('test', 'user')
        );

        $this->assertEquals(
            'testPass',
            Config::getValue('test', 'pass')
        );

        $this->assertEquals(
            1234,
            Config::getValue('test', 'custom')
        );
    }

    public function testComplex()
    {
        $this->assertEquals(
            true,
            Config::getValue(
                'test',
                'complex',
                'configuration',
                'structure'
            )
        );
    }

    public function testUnknownIsNull()
    {
        $this->assertEquals(
            null,
            Config::getValue('test', 'fake')
        );
    }

    public function testEmptyIsNull()
    {
        $this->assertEquals(
            null,
            Config::getValue()
        );
    }
}
