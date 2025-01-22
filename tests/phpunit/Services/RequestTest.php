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

class RequestTest extends TestCase
{
    protected function setUp()
    {
        App::reinitialize();

        $_SERVER['REQUEST_METHOD'] = '';
        $_POST = $_GET = $_REQUEST = [];
    }

    public function testFlash()
    {
        $_POST['name'] = 'a';
        request_flash();
        $this->assertEquals('a', old('name'));
    }

    public function testValidate()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['name' => 'a'];

        $name = validate('name')->required()->get();
        $this->assertEquals('a', $name);

        $last = validate('last', 'z')->required()->get();
        $this->assertEquals('z', $last);
    }

    public function testValidate_msg()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $this->expectException(AppException::class);
        $this->expectExceptionMessage('自定义消息');
        $middle = validate('middle')->required('自定义消息')->get();
        $this->assertEquals('', $middle);
    }

    public function testValidate_title()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $this->expectException(AppException::class);
        $this->expectExceptionMessage('自定义标题不能为空');
        $middle = validate('middle')->required()->get('自定义标题');
        $this->assertEquals('', $middle);
    }

    public function testRequest()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['name' => 'a'];
        input('name');
        input('last', 'z');

        $all = request();
        $this->assertEquals(['name' => 'a', 'last' => 'z'], $all);
    }

    public function testRequest_all()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['name' => '1', 'middle' => '2', 'last' => '3'];
        input('name:i');

        $all = request();
        $this->assertEquals(['name' => '1', 'middle' => '2', 'last' => '3'], $all);
        $this->assertIsInt($all['name']);
    }

    public function testRequest_validate()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [];
        validate('name')->required()->setTitle('姓');
        validate('last')->required()->setTitle('名');

        try {
            request();
        } catch (AppException $e) {
            $this->assertEquals(['name' => '姓不能为空'], $e->getData());
        }
    }

    public function testRequest_validate_parallel()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [];
        validate('name')->required()->setTitle('姓');
        validate('last')->required()->setTitle('名');

        try {
            request(true);
        } catch (AppException $e) {
            $this->assertEquals(['name' => '姓不能为空', 'last' => '名不能为空'], $e->getData());
        }
    }

    public function testInput()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET = ['name' => '1'];
        $this->assertIsInt(input('name:i'));
        $this->assertEquals(1, input('name'));

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['name' => '2'];
        $this->assertIsFloat(input('name:f'));
        $this->assertEquals(2, input('name'));

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_GET = ['name' => '3'];
        $this->assertIsInt(input('get.name:i'));
        $this->assertEquals(3, input('get.name'));

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['name' => ['a' => 1, 'b' => 2]];
        $this->assertIsArray(input('name'));
        // 本身是数组，会自动强制切换为 :a
        $this->assertEquals(['a' => 1, 'b' => 2], input('name:i'));
    }

    public function testSetVirtualPayload()
    {
        svc_request()->setVirtualPayload(['da' => 10]);
        $this->assertEquals(10, input('get.da'));
        $this->assertEquals(10, input('post.da'));
        $this->assertEquals(10, input('request.da'));
    }


}
