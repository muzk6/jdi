<?php


namespace JDI\Services;

/**
 * 白名单
 * @package JDI\Services
 */
class Whitelist
{
    /**
     * @var array 配置文件，格式 core/AppWhitelist.php
     */
    protected $conf;

    /**
     * @var bool 是否为安全环境
     */
    protected $is_safe = false;

    public function __construct(array $conf)
    {
        $this->conf = $conf;
    }

    /**
     * 设置为安全环境
     * @param bool $is_safe
     */
    public function setIsSafe(bool $is_safe = true)
    {
        $this->is_safe = $is_safe;
    }

    /**
     * 当前 IP 是否在白名单内
     * @return bool
     */
    public function isSafeIp()
    {
        if ($this->is_safe) {
            return true;
        }

        $client_ip_str = get_client_ip();
        $client_ip = ip2long($client_ip_str);

        foreach ($this->conf['ip'] as $v) {

            if (strpos($v, '/') === false) {
                if ($v == $client_ip_str) {
                    return true;
                }

            } else {
                list($safe_ip_str, $subnet_num) = explode('/', $v);

                $base = ip2long('255.255.255.255');

                $mask = pow(2, 32 - intval($subnet_num)) - 1; // /24为例则 0.0.0.255(int)
                $subnet_mask = $mask ^ $base; // 子网掩码，/24为例 255.255.255.0(int)

                $safe_ip = ip2long($safe_ip_str);
                if ($safe_ip == ($client_ip & $subnet_mask)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * 检查安全 IP 否则 404
     */
    public function checkSafeIpOrExit()
    {
        if (!$this->isSafeIp()) {
            http_response_code(404);
            exit;
        }
    }

    /**
     * 当前用户是否在白名单内
     * @return bool
     */
    public function isSafeUserId()
    {
        if ($this->is_safe) {
            return true;
        }

        if (!svc_auth()->isLogin()) {
            return false;
        }

        return in_array(svc_auth()->getUserId(), $this->conf['user_id']);
    }

    /**
     * 检查安全用户否则 404
     */
    public function checkSafeUserIdOrExit()
    {
        if (!$this->isSafeUserId()) {
            http_response_code(404);
            exit;
        }
    }

    /**
     * 是否包含白名单安全 Cookie
     * @return bool
     */
    public function isSafeCookie()
    {
        if ($this->is_safe) {
            return true;
        }

        $cookies = is_array($_COOKIE) ? $_COOKIE : [];
        return array_intersect(array_keys($cookies), $this->conf['cookie']) ? true : false;
    }

    /**
     * 检查安全 Cookie 否则 404
     */
    public function checkSafeCookieOrExit()
    {
        if (!$this->isSafeCookie()) {
            http_response_code(404);
            exit;
        }
    }

}
