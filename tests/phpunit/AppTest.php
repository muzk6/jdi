<?php

namespace JDI;


use JDI\Exceptions\FrozenServiceException;
use PHPUnit\Framework\TestCase;

class AppTest extends TestCase
{
    protected function setUp()
    {
        App::$app = null;
        App::init();
    }

    public function testGet()
    {
        App::set('test_value', 123);
        $this->assertEquals(123, App::get('test_value'));

        App::set('test_value', 456);
        $this->assertEquals(456, App::get('test_value'));

        App::set('test_callable', function () {
            return 'ok';
        });
        $this->assertEquals('ok', App::get('test_callable'));

        $this->expectException(FrozenServiceException::class);
        App::set('test_callable', function () {
            return 'frozen';
        });
    }

    public function testIsset()
    {
        App::set('ok', 1);

        $this->assertEquals(true, App::isset('ok'));
        $this->assertEquals(false, App::isset('no'));
    }

    public function testUnset()
    {
        App::set('test_callable', function () {
            return 'ok';
        });
        $this->assertEquals('ok', App::get('test_callable'));

        App::unset('test_callable');
        App::set('test_callable', function () {
            return 'new ok';
        });
        $this->assertEquals('new ok', App::get('test_callable'));
    }

    public function testReset()
    {
        App::set('test_callable', function () {
            return 'ok';
        });
        $this->assertEquals('ok', App::get('test_callable'));

        App::reset('test_callable', function () {
            return 'new ok';
        });
        $this->assertEquals('new ok', App::get('test_callable'));
    }

    public function testSingleton()
    {
        $flag = 0;
        $svc = function () use (&$flag) {
            return App::singleton('test_singleton', function () use (&$flag) {
                $flag++;
                return 'singleton';
            });
        };

        $this->assertEquals('singleton', $svc());

        $svc();
        $this->assertEquals(1, $flag);
    }

    public function testReinitialize()
    {
        App::$app = null;
        App::init(['foo' => 1]);
        App::set('bar', 1);
        App::reinitialize();

        $this->assertEquals(1, App::get('foo'));
        $this->assertEquals(false, App::isset('bar'));

        App::$app = null;
        App::init(['foo2' => 1]);
        App::set('bar2', 1);
        App::reinitialize();

        $this->assertEquals(1, App::get('foo2'));
        $this->assertEquals(false, App::isset('bar2'));
        $this->assertEquals(false, App::isset('foo'));
        $this->assertEquals(false, App::isset('bar'));
    }
}
