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

/**
 * CURL 简单封装
 * @package JDI\Services
 */
class CURL
{
    /**
     * @var false|resource cURL 句柄
     */
    protected $ch;

    public function __construct()
    {
        $this->ch = curl_init();
    }

    public function __destruct()
    {
        curl_close($this->ch);
    }

    /**
     * POST 请求
     * @param string|array $url
     * <p>string: 'http://sparrow.com/demo' 一般用于固定 url 的场景</p>
     * <p>array: ['rpc.sparrow', '/demo'] 即读取配置 url.php 里的域名再拼接上 /demo 一般用于不同环境不同 url 的场景</p>
     * @param array $data POST 参数
     * @param array $headers 请求头
     * @param int $connect_timeout 请求超时(秒)
     * @return array|string|null
     */
    public function post($url, array $data = [], array $headers = [], int $connect_timeout = 3)
    {
        if (is_array($url)) {
            $url = Utils::url($url);
        }

        curl_reset($this->ch);
        curl_setopt_array($this->ch, [
            CURLOPT_URL => $url,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_POST => true,
            CURLOPT_CONNECTTIMEOUT => $connect_timeout,
            CURLOPT_POSTFIELDS => http_build_query($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
        ]);

        if ($headers) {
            curl_setopt($this->ch, CURLOPT_HTTPHEADER, $headers);
        }

        $out = curl_exec($this->ch);

        $info = curl_getinfo($this->ch);
        if ($info['http_code'] != 200) {
            trigger_error(json_encode($info, JSON_UNESCAPED_SLASHES), E_USER_WARNING);
            return null;
        }

        return json_decode($out, true) ?: $out;
    }

    /**
     * GET 请求
     * @param string|array $url
     * <p>string: 'http://sparrow.com/demo' 一般用于固定 url 的场景</p>
     * <p>array: ['rpc.sparrow', '/demo'] 即读取配置 url.php 里的域名再拼接上 /demo 一般用于不同环境不同 url 的场景</p>
     * @param array $data querystring 参数
     * @param array $headers 请求头
     * @param int $connect_timeout 请求超时(秒)
     * @return bool|string|null
     */
    public function get($url, array $data = [], array $headers = [], int $connect_timeout = 3)
    {
        if (is_array($url)) {
            $url = Utils::url($url);
        }

        if ($data) {
            $url .= (strpos($url, '?') !== false ? '&' : '?') . http_build_query($data);
        }

        curl_reset($this->ch);
        curl_setopt_array($this->ch, [
            CURLOPT_URL => $url,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_CONNECTTIMEOUT => $connect_timeout,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
        ]);

        if ($headers) {
            curl_setopt($this->ch, CURLOPT_HTTPHEADER, $headers);
        }

        $out = curl_exec($this->ch);

        $info = curl_getinfo($this->ch);
        if ($info['http_code'] != 200) {
            trigger_error(json_encode($info, JSON_UNESCAPED_SLASHES), E_USER_WARNING);
            return null;
        }

        return json_decode($out, true) ?: $out;
    }

}
