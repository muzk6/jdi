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
use PHPUnit\Framework\TestCase;

class LogTest extends TestCase
{
    protected function setUp()
    {
        App::reinitialize();
    }

    /**
     * 自动刷新
     */
    public function testAutoFlush()
    {
        svc_log()->setFlushHandler(function ($logs) {
            $this->assertEquals('index1', $logs[0]['index']);
            $this->assertEquals('type1', $logs[0]['type']);
            $this->assertEquals('hello', $logs[0]['data']);
            $this->assertEquals(1, $logs[0]['extra_data']['extra_num']);
            $this->assertEquals(['extra_arr' => 1], $logs[0]['extra_data']['extra_arr']);

            $this->assertEquals('index2', $logs[1]['index']);
            $this->assertEquals('app', $logs[1]['type']);
            $this->assertEquals(['foo' => 1], $logs[1]['data']);
            $this->assertEquals(1, $logs[1]['extra_data']['extra_num']);
            $this->assertEquals(['extra_arr' => 1], $logs[1]['extra_data']['extra_arr']);
        });

        log_push('index1', 'hello', 'type1');
        log_push('index2', ['foo' => 1]);
        svc_log()->setExtraData('extra_num', 1);
        svc_log()->setExtraData('extra_arr', ['extra_arr' => 1]);

        $this->assertTrue(true);
    }

    /**
     * 手动刷新
     */
    public function testNoAutoFlush()
    {
        svc_log()->autoFlush(false);

        $rs = [];
        svc_log()->setFlushHandler(function ($logs) use (&$rs) {
            $rs = $logs;
        });

        log_push('index1', 'hello', 'type1');
        log_push('index2', ['foo' => 1]);
        svc_log()->setExtraData('extra_num', 1);
        svc_log()->setExtraData('extra_arr', ['extra_arr' => 1]);

        svc_log()->flush();

        $this->assertEquals('index1', $rs[0]['index']);
        $this->assertEquals('type1', $rs[0]['type']);
        $this->assertEquals('hello', $rs[0]['data']);
        $this->assertEquals(1, $rs[0]['extra_data']['extra_num']);
        $this->assertEquals(['extra_arr' => 1], $rs[0]['extra_data']['extra_arr']);

        $this->assertEquals('index2', $rs[1]['index']);
        $this->assertEquals('app', $rs[1]['type']);
        $this->assertEquals(['foo' => 1], $rs[1]['data']);
        $this->assertEquals(1, $rs[1]['extra_data']['extra_num']);
        $this->assertEquals(['extra_arr' => 1], $rs[1]['extra_data']['extra_arr']);
    }

}