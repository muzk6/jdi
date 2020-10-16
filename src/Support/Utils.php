<?php

namespace JDI\Support;

use Exception;
use JDI\Exceptions\AppException;
use stdClass;

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
        // 先刷出 buffer, 避免被后面的 header 影响
        if (ob_get_status()) {
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
            foreach ($matches[0] AS $xip) {
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
            $conf = svc_config()->get('url');
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

}
