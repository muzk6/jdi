<?php

namespace JDI\Tests\Services;

use JDI\App;
use JDI\Services\Whitelist;
use PHPUnit\Framework\TestCase;

class WhitelistTest extends TestCase
{
    public static function setUpBeforeClass()
    {
        App::set('JDI\Support\Svc::whitelist', function () {
            return new Whitelist([
                'ip' => [
                    '127.0.0.1',
                ],
                'cookie' => [
                    'my_cookie'
                ],
                'user_id' => [
                    1010
                ],
            ]);
        });
    }

    public static function tearDownAfterClass()
    {
        App::unset('JDI\Support\Svc::whitelist');
    }

    public function testIsSafeUserId()
    {
        $this->assertEquals(true, svc_whitelist()->isSafeUserId(1010));
        $this->assertEquals(false, svc_whitelist()->isSafeUserId(1011));
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
