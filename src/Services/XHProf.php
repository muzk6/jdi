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

/**
 * XHProf
 * @package JDI\Services
 */
class XHProf
{
    /**
     * @var int 开关
     */
    protected $enable;

    /**
     * @var double 采样概率，1=100%
     */
    protected $probability;

    /**
     * @var double 最小耗时，单位秒，超时则记录否则跳过
     */
    protected $min_time;

    /**
     * @var string 数据目录
     */
    protected $path_data;

    /**
     * @var double 统计开始时间
     */
    protected $start_time;

    public function __construct(array $conf)
    {
        $this->enable = $conf['enable'];
        $this->probability = $conf['probability'];
        $this->min_time = $conf['min_time'];
        $this->path_data = $conf['path_data'];

        if (!file_exists($this->path_data)) {
            mkdir($this->path_data, 0755, true);
        }
    }

    /**
     * 按配置自动开启
     * @return bool
     */
    public function auto()
    {
        if (empty($this->enable)) {
            return false;
        }

        if (!(mt_rand(1, 100) <= $this->probability * 100)) {
            return false;
        }

        return $this->start();
    }

    /**
     * 手动开启
     * @return bool
     */
    public function start()
    {
        if (!extension_loaded('tideways_xhprof')) {
            trigger_error('请安装扩展: tideways_xhprof', E_USER_WARNING);
            return false;
        }

        $this->start_time = microtime(true);
        tideways_xhprof_enable(TIDEWAYS_XHPROF_FLAGS_CPU | TIDEWAYS_XHPROF_FLAGS_MEMORY);

        $alias = $this;
        register_shutdown_function(function () use ($alias) {
            $alias->shutdown();
        });

        return true;
    }

    /**
     * 结束回调
     * @return bool
     */
    protected function shutdown()
    {
        $end_time = microtime(true);
        $cost_time = $end_time - $this->start_time;
        if ($cost_time < $this->min_time) {
            return false;
        }

        $path = $this->path_data;
        if (!file_exists($path)) {
            mkdir($path, 0755, true);
        }

        if (PHP_SAPI == 'cli') {
            $cmd = basename($_SERVER['argv'][0]);
            $url = $cmd . ' ' . implode(' ', array_slice($_SERVER['argv'], 1));
        } else {
            $url = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        }

        $name = "{$url};{$cost_time}";
        $name = rtrim(str_replace(['+', '/'], ['-', '_'], base64_encode($name)), '=');

        $data = tideways_xhprof_disable();
        file_put_contents(
            sprintf('%s/%s.%s.xhprof', $path, $name, uniqid()),
            serialize($data)
        );
    }
}
