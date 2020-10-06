<?php

namespace JDI\Tests\Services;

use PHPUnit\Framework\TestCase;

class AuthTest extends TestCase
{
    public function test()
    {
        svc_auth()->login(1111);
        $this->assertEquals(true, svc_auth()->isLogin());
        $this->assertEquals(1111, svc_auth()->getUserId());

        svc_auth()->logout();
        $this->assertEquals(false, svc_auth()->isLogin());
        $this->assertEquals(0, svc_auth()->getUserId());
    }
}
