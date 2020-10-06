<?php

namespace JDI\Tests\Services;

use PHPUnit\Framework\TestCase;

class CURLTest extends TestCase
{
    public function testPost()
    {
        $rs = curl_post('baidu.com');
        $this->assertNotEmpty($rs);
    }

    public function testGet()
    {
        $rs = curl_get('baidu.com');
        $this->assertNotEmpty($rs);
    }
}
