<?php

use Dero\Core\Retval;

class RetvalTest extends PHPUnit_Framework_TestCase
{
    public function testSuccess()
    {
        $oRetval = new Retval();
        $oRetval->set(['key' => 'value']);

        $this->assertFalse($oRetval->hasFailure());

        $mRet = $oRetval->get();

        $this->assertNotEmpty($mRet);
        $this->assertTrue(is_array($mRet));
        $this->assertArrayHasKey('key', $mRet);
        $this->assertEquals(
            $mRet['key'],
            'value'
        );
    }

    public function testSingleError()
    {
        $oException = new Exception('test');
        $oRetval = new Retval();
        $oRetval->addError('string error', $oException);

        $this->assertTrue($oRetval->hasFailure());
        $this->assertEquals('string error', $oRetval->getError());
        $this->assertEquals($oException, $oRetval->getException());
    }

    public function testMultiError()
    {
        $aExceptions = [
            new Exception('first exception'),
            new Exception('second exception'),
        ];
        $aErrors = [
            'first error',
            'second error',
        ];
        $oRetval = new Retval();
        $oRetval->addError($aErrors[0], $aExceptions[0]);
        $oRetval->addError($aErrors[1], $aExceptions[1]);

        $this->assertTrue($oRetval->hasFailure());
        $this->assertEquals($aErrors, $oRetval->getError());
        $this->assertEquals($aExceptions, $oRetval->getException());
    }
}
