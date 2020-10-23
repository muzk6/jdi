<?php

namespace JDI;


use JDI\Exceptions\FrozenServiceException;
use PHPUnit\Framework\TestCase;

class AppTest extends TestCase
{
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

        App::unset('test_callable');
        App::set('test_callable', function () {
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
}
