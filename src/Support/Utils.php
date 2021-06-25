<?php

/*
 * This file is part of the muzk6/jdi.
 *
 * (c) muzk6 <muzk6x@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace JDI\Support;

use Exception;
use JDI\Exceptions\AppException;
use stdClass;

/**
 * 通用工具类
 * @package JDI\Support
 */
class Utils
{
    /**
     * API 格式化
     * @param bool|AppException|Exception $state 业务状态，异常对象时自动填充后面的参数
     * @param array $data 对象体
     * @param string $message 消息体
     * @param int $code 消息码
     * @return array
     */
    public static function api_format($state, array $data = [], string $message = '', int $code = 0)
    {
        $body = [
            's' => false,
            'c' => $code,
            'm' => $message,
            'd' => $data,
        ];

        if ($state instanceof Exception) {
            $exception = $state;

            empty($code) && $body['c'] = $exception->getCode();
            empty($message) && $body['m'] = $exception->getMessage();

            if (empty($body['d']) && ($exception instanceof AppException)) {
                $body['d'] = $exception->getData();
            }
        } else {
            $body['s'] = boolval($state);
        }

        $body['s'] = boolval($body['s']);
        $body['c'] = intval($body['c']);
        $body['m'] = strval($body['m']);

        return $body;
    }

    /**
     * JSON 类型的 API 格式
     * @param int|bool|AppException|Exception $state 业务状态，异常对象时自动填充后面的参数
     * @param array $data 对象体
     * @param string $message 消息体
     * @param int $code 消息码
     * @return string
     */
    public static function api_json($state, array $data = [], string $message = '', int $code = 0)
    {
        // 先刷出 buffer, 避免被后面的 header Content-Type 影响，导致不能正常输出前面的内容
        if (ob_get_length()) {
            ob_flush();
            flush();
        }

        headers_sent() || header('Content-Type: application/json; Charset=UTF-8');

        $body = self::api_format($state, $data, $message, $code);
        if (empty($body['d'])) {
            $body['d'] = new stdClass();
        }

        return json_encode($body);
    }

    /**
     * 输出成功状态的消息体
     * @param string $message 消息体
     * @return string
     */
    public static function api_msg(string $message)
    {
        return self::api_json(true, [], $message, 0);
    }

    /**
     * 客户端IP
     * @return string
     */
    public static function get_client_ip()
    {
        $ip = '';
        if (isset($_SERVER['HTTP_CDN_SRC_IP'])) {
            $ip = $_SERVER['HTTP_CDN_SRC_IP'];
        } elseif (isset($_SERVER['HTTP_CLIENT_IP'])
            && preg_match('/^([0-9]{1,3}\.){3}[0-9]{1,3}$/', trim($_SERVER['HTTP_CLIENT_IP']))) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])
            && preg_match_all('#\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}#s', trim($_SERVER['HTTP_X_FORWARDED_FOR']), $matches)) {
            foreach ($matches[0] as $xip) {
                $xip = trim($xip);
                if (filter_var($xip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE)) {
                    $ip = $xip;
                    break;
                }
            }
        } elseif (isset($_SERVER['HTTP_X_REAL_IP'])) {
            $ip = $_SERVER['HTTP_X_REAL_IP'];
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        return filter_var(trim($ip), FILTER_VALIDATE_IP) ?: '';
    }

    /**
     * 带协议和域名的完整URL
     * <p>
     * 当前域名URL：url('/path/to')<br>
     * 其它域名URL：url(['test', '/path/to'])
     * </p>
     * @param string|array $path URL路径
     * @param array $params Query String
     * @param bool $secure 是否为安全协议
     * @return string
     */
    public static function url($path, array $params = [], bool $secure = false)
    {
        if (is_array($path)) {
            if (count($path) !== 2) {
                trigger_error("正确用法：url(['test', '/path/to'])", E_USER_ERROR);
            }

            list($alias, $path) = $path;
            $conf = Svc::config()->get('url');
            if (!isset($conf[$alias])) {
                trigger_error("url.php 不存在配置项: {$alias}", E_USER_ERROR);
            }

            $host = $conf[$alias];
        } else {
            $host = $_SERVER['HTTP_HOST'] ?? '';
        }

        if ($host) {
            $protocol = $secure ? 'https://' : 'http://';
            $host = $protocol . $host;
        }

        if ($params) {
            $path .= strpos($path, '?') !== false ? '&' : '?';
            $path .= http_build_query($params);
        }

        return $host . $path;
    }

    /**
     * 读取、设置 配置
     * <p>
     * 读取 config/dev/app.php 里的 lang 配置：config('app.lang')<br>
     * 设置：config(['app.lang' => 'en'])
     * </p>
     * @param string|array $key string时读取，array时设置
     * @return bool|mixed
     */
    public static function config($key)
    {
        if (is_array($key)) {
            $ret = false;
            foreach ($key as $k => $v) {
                $ret = Svc::config()->set($k, $v);
            }

            return $ret;
        } else {
            return Svc::config()->get($key);
        }
    }

    /**
     * 网页后退
     * @param int $backward 1.跳转到上一页；2.后退到上一页（保留上一页的数据）；
     * @param bool|int $exit 是否 exit
     */
    public static function back(int $backward = 1, $exit = true)
    {
        if ($backward == 1) {
            header('Location: ' . $_SERVER['HTTP_REFERER']);
        } elseif ($backward == 2) {
            echo '<script>history.back();</script>';
        }

        if ($exit) {
            exit;
        }
    }

    /**
     * 网页跳转
     * <p>redirect('/foo/bar') 跳转到当前域名的 /foo/bar 地址去</p>
     * <p>redirect('https://google.com') 跳转到谷歌</p>
     * @param string $url
     * @param bool|int $exit 是否 exit
     */
    public static function redirect(string $url, $exit = true)
    {
        header('Location: ' . $url);

        if ($exit) {
            exit;
        }
    }

    /**
     * JS alert() 并跳转回上一页
     * @param string $msg
     * @param int $backward 0.不跳转后退；1.跳转到上一页；2.后退到上一页（保留上一页的数据）；
     * @param bool|int $exit 是否 exit
     */
    public static function alert(string $msg, int $backward = 1, $exit = true)
    {
        $alert = "alert('{$msg}');";

        if ($backward == 1) {
            $back = "location.href='{$_SERVER['HTTP_REFERER']}';";
        } elseif ($backward == 2) {
            $back = 'history.back();';
        } else {
            $back = '';
        }

        echo "<script>{$alert}{$back}</script>";

        if ($exit) {
            exit;
        }
    }

    /**
     * 频率限制
     * <p>ttl秒 内限制 limit次</p>
     * @param string $key 缓存key
     * @param int $limit 限制次数
     * @param int $ttl 指定秒数内
     * @return int 剩余次数，0表示这次是最后一次通过，下次就触发限制
     * @throws AppException ['reset' => 重置的时间点]
     */
    public static function throttle(string $key, int $limit, int $ttl)
    {
        $now = time();
        $len = 0;

        if (Svc::redis()->lLen($key) < $limit) {
            $len = Svc::redis()->lPush($key, $now);
        } else {
            $earliest = intval(Svc::redis()->lIndex($key, -1));
            if ($now - $earliest < $ttl) {
                Svc::redis()->expire($key, $ttl);
                AppException::panic(10001001, [
                    'reset' => $earliest + $ttl,
                ]);
            } else {
                Svc::redis()->lTrim($key, 1, 0);
                $len = Svc::redis()->lPush($key, $now);
            }
        }

        Svc::redis()->expire($key, $ttl);
        return $limit - $len;
    }

}
