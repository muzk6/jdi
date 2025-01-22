<?php

/*
 * This file is part of the muzk6/jdi.
 *
 * (c) muzk6 <muzk6x@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */


namespace JDI\Tests\Services;

use JDI\App;
use JDI\Exceptions\AppException;
use PHPUnit\Framework\TestCase;

class RouterTest extends TestCase
{
    protected function setUp()
    {
        App::reinitialize();
    }

    public function testAddMiddleware_root()
    {
        $rs = '';

        route_get('/root', function () use (&$rs) {
            $rs .= 'root;';
        });

        // 其它分组的中间件不执行
        route_group(function () use (&$rs) {
            route_middleware(function () use (&$rs) {
                $rs .= 'group_middle;';
            });
        });

        route_middleware(function () use (&$rs) {
            $rs .= 'group_middle_after;';
        });

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/root?a=1';
        route_dispatch();
        $this->assertTrue(true);

        // 因为后置中间件也是在 register_shutdown_function() 里执行的
        register_shutdown_function(function () use (&$rs) {
            $this->assertEquals('root;group_middle_after;', $rs);
        });
    }

    public function testAddMiddleware_group()
    {
        $rs = '';

        route_middleware(function () use (&$rs) {
            $rs .= 'root_middle1;';
        });

        route_middleware(function () use (&$rs) {
            $rs .= 'root_middle2;';
        });

        route_group(function () use (&$rs) {
            route_middleware(function () use (&$rs) {
                $rs .= 'group_middle;';
            });

            route_get('/group', function () use (&$rs) {
                $rs .= 'group;';
            });
        });

        route_middleware(function () use (&$rs) {
            $rs .= 'group_middle_after;';
        });

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/group?a=1';
        route_dispatch();
        $this->assertTrue(true);

        // 因为后置中间件也是在 register_shutdown_function() 里执行的
        register_shutdown_function(function () use (&$rs) {
            $this->assertEquals('group_middle;root_middle1;root_middle2;group;group_middle_after;', $rs);
        });
    }

    public function testAddRoute_get()
    {
        $rs = '';
        route_get('/get', function () use (&$rs) {
            $rs = 'get';
            panic('The AppException.');
        });

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/get?a=1';
        route_dispatch();

        $this->assertEquals('get', $rs);
        $this->assertEquals(['method' => 'GET', 'url' => '/get', 'is_regexp' => false], svc_router()->getMatchedRoute());
        $this->assertEquals('The AppException.', svc_router()->getException()->getMessage());
    }

    public function testAddRoute_any_re()
    {
        $rs = '';
        route_any('#any(_\d+)?#i', function () use (&$rs) {
            $rs .= 'any;';
            throw new \Exception('The Exception;');
        }, function (\Exception $exception) use (&$rs) {
            $rs .= $exception->getMessage();
        });

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/ANY_123?a=1';
        route_dispatch();

        $this->assertEquals('any;The Exception;', $rs);
        $this->assertEquals(['method' => 'ANY', 'url' => '#any(_\d+)?#i', 'is_regexp' => true], svc_router()->getMatchedRoute());
        $this->assertEquals(['ANY_123', '_123'], svc_router()->getREMatches());
    }

    public function testFireStatus404()
    {
        $rs = '';
        register_shutdown_function(function () use (&$rs) {
            $this->assertEquals('fire', $rs);
        });

        svc_router()->setStatus404Handler(function () use (&$rs) {
            $rs = 'fire';
        });

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REQUEST_URI'] = '/';
        route_dispatch();
    }

    public function testSetResponseContent()
    {
        $rs = '';

        route_get('/', function () {
            return 'content';
        });

        route_middleware(function () {
            $content = svc_router()->getResponseContent();
            svc_router()->setResponseContent("new_{$content}");
        });

        route_middleware(function () use (&$rs) {
            $rs = svc_router()->getResponseContent();
        });

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/';
        route_dispatch();

        register_shutdown_function(function () use (&$rs) {
            $this->assertEquals('new_content', $rs);
        });

        $this->assertEquals(['method' => 'GET', 'url' => '/', 'is_regexp' => false], svc_router()->getMatchedRoute());
    }

    public function testSetResponseContent_catch()
    {
        $rs = '';

        route_get('/', function () {
            panic('AppException');
        }, function (AppException $exception) {
            return 'catch:' . $exception->getMessage();
        });

        route_middleware(function () use (&$rs) {
            $rs = svc_router()->getResponseContent();
        });

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/';
        route_dispatch();

        register_shutdown_function(function () use (&$rs) {
            $this->assertEquals('catch:AppException', $rs);
        });

        $this->assertEquals(['method' => 'GET', 'url' => '/', 'is_regexp' => false], svc_router()->getMatchedRoute());
    }

    public function testSimulate()
    {
        $rs = '';

        route_get('/demo/index', function() {
            return [input('get.da:i'), svc_auth()->isLogin()];
        });

        route_middleware(function () use (&$rs) {
            $rs = svc_router()->getResponseContent();
        });

        route_simulate('/demo/index', ['da' => 10], 1010);

        register_shutdown_function(function () use (&$rs) {
            $this->assertEquals([10, true], $rs);
        });

        $this->assertEquals(['method' => 'GET', 'url' => '/demo/index', 'is_regexp' => false], svc_router()->getMatchedRoute());
    }

}
