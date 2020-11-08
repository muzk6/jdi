<?php

/*
 * This file is part of the muzk6/jdi.
 *
 * (c) muzk6 <muzk6x@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace JDI\Services;


use JDI\Support\Utils;

class Log
{
    /**
     * @var callable 刷写日志的回调
     */
    protected $flush_handler;

    /**
     * @var array 自定义额外内容
     */
    protected $extra_data = [];

    /**
     * @var array 待刷写的日志集合
     */
    protected $logs = [];

    /**
     * @var bool 是否自动刷写日志
     */
    protected $is_auto_flush = true;

    public function __construct()
    {
        register_shutdown_function(function () {
            if ($this->is_auto_flush) {
                $this->flush();
            }
        });
    }

    /**
     * 是否自动刷写日志
     * @param bool $is_auto_flush
     */
    public function autoFlush(bool $is_auto_flush)
    {
        $this->is_auto_flush = $is_auto_flush;
    }

    /**
     * 刷写日志
     */
    public function flush()
    {
        if (!is_callable($this->flush_handler)) {
            trigger_error('请先调用 \JDI\Services\Log::setFlushHandler 定义刷写回调', E_USER_WARNING);
            return;
        }

        foreach ($this->logs as &$log) {
            $log['extra_data'] = $this->extra_data;
        }
        unset($log);

        call_user_func($this->flush_handler, $this->logs);
        $this->logs = []; // 清空日志集合
    }

    /**
     * 自定义额外内容
     * @param string $name
     * @param $data
     */
    public function setExtraData(string $name, $data)
    {
        $this->extra_data[$name] = $data;
    }

    /**
     * 设置刷写日志的回调
     * @param callable $flush_handler function ($logs) {}
     */
    public function setFlushHandler(callable $flush_handler)
    {
        $this->flush_handler = $flush_handler;
    }

    /**
     * 日志推入待刷写集合
     * @param string $index 日志名
     * @param array|string $data 日志内容
     * @param string $type 日志类型
     */
    public function push(string $index, $data = '', string $type = 'app')
    {
        $log = [
            'time' => date('Y-m-d H:i:s'),
            'index' => $index,
            'type' => $type,
            'request_id' => isset($_SERVER['REQUEST_TIME_FLOAT']) ? md5(strval($_SERVER['REQUEST_TIME_FLOAT'])) : '',
            'hostname' => php_uname('n'),
            'sapi' => PHP_SAPI,
            'script_filename' => $_SERVER['SCRIPT_FILENAME'] ?? '',
        ];

        // fpm
        if (PHP_SAPI != 'cli') {
            $log['method'] = $_SERVER['REQUEST_METHOD'] ?? '';
            $log['host'] = $_SERVER['HTTP_HOST'] ?? '';
            $log['url'] = $_SERVER['REQUEST_URI'] ?? '';
            $log['client_ip'] = Utils::get_client_ip();
            $log['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
        }

        $log['data'] = $data;
        $this->logs[] = $log;
    }

}
