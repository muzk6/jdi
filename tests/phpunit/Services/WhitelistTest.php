<?php

namespace JDI\Tests\Services;

use JDI\App;
use JDI\Services\Whitelist;
use PHPUnit\Framework\TestCase;

class WhitelistTest extends TestCase
{
    public static function setUpBeforeClass()
    {
        App::set('service.whitelist', new Whitelist([
            'ip' => [
                '127.0.0.1',
            ],
            'cookie' => [
                'my_cookie'
            ],
            'user_id' => [
                1010
            ],
        ]));
    }

    public function testIsSafeUserId()
    {
        svc_auth()->login(1010);
        $this->assertEquals(true, svc_whitelist()->isSafeUserId());

        svc_auth()->logout();
        $this->assertEquals(false, svc_whitelist()->isSafeUserId());
    }

    public function testIsSafeCookie()
    {
        $_COOKIE['my_cookie'] = 1;
        $this->assertEquals(true, svc_whitelist()->isSafeCookie());

        unset($_COOKIE['my_cookie']);
        $this->assertEquals(false, svc_whitelist()->isSafeCookie());
    }

    public function testIsSafeIp()
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $this->assertEquals(true, svc_whitelist()->isSafeIp());

        $_SERVER['REMOTE_ADDR'] = '1.1.1.1';
        $this->assertEquals(false, svc_whitelist()->isSafeIp());
    }

}
