<?php

namespace JDI\Tests\Services;

use JDI\App;
use JDI\Exceptions\AppException;
use JDI\Services\XSRF;
use PHPUnit\Framework\TestCase;

class XSRFTest extends TestCase
{
    public function testToken()
    {
        $token = xsrf_token();
        $this->assertNotEmpty($token);

        return $token;
    }

    /**
     * @depends testToken
     */
    public function testRefresh()
    {
        $this->assertEquals(0, svc_xsrf()->refresh());
    }

    /**
     * @depends testToken
     * @param string $token
     * @throws AppException
     */
    public function testCheck(string $token)
    {
        $_REQUEST['_token'] = $token;
        $this->assertEquals(true, svc_xsrf()->check());

        $_REQUEST['_token'] = '';
        $this->expectExceptionCode(10001001);
        svc_xsrf()->check();

        $_REQUEST['_token'] = 'other';
        $this->expectExceptionCode(10001001);
        svc_xsrf()->check();

        unset($_SESSION['xsrf_token']);
        $this->expectExceptionCode(10001002);
        svc_xsrf()->check();
    }

    /**
     * @throws AppException
     */
    public function testExpired()
    {
        App::set('svc_xsrf', new XSRF(['expire' => -1]));

        $_REQUEST['_token'] = xsrf_token();
        $this->expectExceptionCode(10001002);
        svc_xsrf()->check();
    }
}
