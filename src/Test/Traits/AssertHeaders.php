<?php

namespace Dero\Test\Traits;

trait AssertHeaders
{
    protected function assertHeaderStatus($iStatusCode)
    {
        $iActualCode = http_response_code();
        if ($iActualCode !== false)
            $this->assertEquals($iStatusCode, $iActualCode);
    }

    protected function assertHeaderSet($strKey, $mValue)
    {
        $aHeaders = [];
        foreach(xdebug_get_headers() as $strHeader)
        {
            $aHeader = explode(':',$strHeader);
            $aHeaders[$aHeader[0]] = $aHeader[1];
        }

        $this->assertArrayHasKey($strKey, $aHeaders);
        $this->assertContains($mValue, $aHeaders[$strKey]);
    }
}