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
     * @var string 日志路径
     */
    protected $path_data;

    /**
     * @var array 日志 data 内容
     */
    protected $data = [];

    /**
     * @var array 执行写日志的回调集合
     */
    protected $write_handler = [];

    public function __construct(array $conf)
    {
        $this->path_data = $conf['path_data'];

        register_shutdown_function(function () {
            foreach ($this->write_handler as $handler) {
                call_user_func($handler);
            }
        });
    }

    /**
     * 设置日志 data 内容<br>
     * 场景：这里设置后，logfile() 会自动带上这些内容
     * @param string $name
     * @param $data
     */
    public function setData(string $name, $data)
    {
        $this->data[$name] = $data;
    }

    /**
     * 文件日志
     * @param string $index 日志名(索引)
     * @param array|string $data 日志内容
     * @param string $filename 日志文件名前缀
     */
    public function file(string $index, $data = '', string $filename = 'app')
    {
        $filename = trim(str_replace('/', '', $filename));

        $log = [
            'time' => date('Y-m-d H:i:s'),
            'index' => $index,
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

        $path = sprintf('%s/%s_%s.log',
            $this->path_data, $filename, date('Ym'));

        $this->write_handler[] = function () use ($data, $path, $log) {
            if (!is_array($data)) {
                $data = strlen($data) ? ['-' => $data] : []; // 非数组时强制转为带 - 为 key 的数组元素，因为默认数字 key 会被 JSON 重新排序
            }

            $log['data'] = array_merge($this->data, $data);
            $log = json_encode($log, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);

            file_put_contents($path, $log . PHP_EOL, FILE_APPEND);
        };
    }

}
