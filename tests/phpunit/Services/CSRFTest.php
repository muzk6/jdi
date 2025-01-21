<?php

namespace JDI\Tests\Services;

use JDI\App;
use JDI\Exceptions\AppException;
use JDI\Services\CSRF;
use PHPUnit\Framework\TestCase;

class CSRFTest extends TestCase
{
    public function testToken()
    {
        $token = csrf_token();
        $this->assertNotEmpty($token);

        return $token;
    }

    /**
     * @depends testToken
     */
    public function testRefresh()
    {
        $this->assertEquals(0, svc_csrf()->refresh());
    }

    /**
     * @depends testToken
     * @param string $token
     * @throws AppException
     */
    public function testCheck(string $token)
    {
        $_REQUEST['_token'] = $token;
        $this->assertEquals(true, svc_csrf()->check());

        $_REQUEST['_token'] = '';
        $this->expectExceptionCode(10001001);
        svc_csrf()->check();

        $_REQUEST['_token'] = 'other';
        $this->expectExceptionCode(10001001);
        svc_csrf()->check();

        unset($_SESSION['csrf_token']);
        $this->expectExceptionCode(10001002);
        svc_csrf()->check();
    }

    /**
     * @throws AppException
     */
    public function testExpired()
    {
        App::set('JDI\Support\Svc::csrf', function () {
            return new CSRF(['expire' => -1]);
        });

        $_REQUEST['_token'] = csrf_token();
        $this->expectExceptionCode(10001002);
        svc_csrf()->check();
    }
}
