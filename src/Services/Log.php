<?php

/*
 * This file is part of the muzk6/jdi.
 *
 * (c) muzk6 <muzk6x@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/*
 * This file is part of the muzk6/jdi.
 *
 * (c) muzk6 <muzk6x@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */


namespace JDI\Services;


use JDI\Support\Svc;
use JDI\Support\Utils;

class Log
{
    /**
     * @var string 日志路径
     */
    protected $path_data;

    public function __construct(array $conf)
    {
        $this->path_data = $conf['path_data'];
    }

    /**
     * 文件日志
     * @param string $index 日志名(索引)
     * @param array|string $data 日志内容
     * @param string $filename 日志文件名前缀
     * @return bool|int
     */
    public function file(string $index, $data, string $filename = 'app')
    {
        $filename = trim(str_replace('/', '', $filename));

        $log = json_encode([
            'time' => date('Y-m-d H:i:s'),
            'index' => $index,
            'request_id' => isset($_SERVER['REQUEST_TIME_FLOAT']) ? md5(strval($_SERVER['REQUEST_TIME_FLOAT'])) : '',
            'hostname' => php_uname('n'),
            'sapi' => PHP_SAPI,
            'script_filename' => $_SERVER['SCRIPT_FILENAME'] ?? '',
            'method' => $_SERVER['REQUEST_METHOD'] ?? '',
            'host' => $_SERVER['HTTP_HOST'] ?? '',
            'url' => $_SERVER['REQUEST_URI'] ?? '',
            'client_ip' => Utils::get_client_ip(),
            'user_id' => Svc::auth()->getUserId(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'data' => $data,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);

        $path = sprintf('%s/%s_%s.log',
            $this->path_data, $filename, date('Ym'));

        return file_put_contents($path, $log . PHP_EOL, FILE_APPEND);
    }
}
