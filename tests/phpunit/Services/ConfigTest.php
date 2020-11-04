<?php

namespace JDI\Tests\Services;

use JDI\App;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    protected static $display_errors;

    public static function setUpBeforeClass()
    {
        App::reinitialize();

        self::$display_errors = ini_get('display_errors');
        ini_set('display_errors', 0);
    }

    public static function tearDownAfterClass()
    {
        ini_set('display_errors', self::$display_errors);
    }

    public function testExists()
    {
        $this->assertEquals(true, svc_config()->exists('lang_en'));
    }

    public function testGet()
    {
        $this->assertEquals('Parameter error', svc_config()->get('lang_en.10001000'));
        $this->assertEquals('Parameter error', config('lang_en.10001000'));
        $this->assertEquals('', svc_config()->get('not_exists'));
    }

    public function testSet()
    {
        svc_config()->set('test.p1.p2', 'hello');
        $this->assertEquals('hello', svc_config()->get('test.p1.p2'));

        config(['test.p1.p2' => 'hello2']);
        $this->assertEquals('hello2', config('test.p1.p2'));
    }
}
