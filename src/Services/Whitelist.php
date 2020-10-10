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

    public function __construct(array $conf)
    {
        $this->conf = $conf;
    }

    /**
     * 当前 IP 是否在白名单内
     * @return bool
     */
    public function isSafeIp()
    {
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
     * 当前用户是否在白名单内
     * @return bool
     */
    public function isSafeUserId()
    {
        if (!svc_auth()->isLogin()) {
            return false;
        }

        return in_array(svc_auth()->getUserId(), $this->conf['user_id']);
    }

    /**
     * 是否包含白名单安全 Cookie
     * @return bool
     */
    public function isSafeCookie()
    {
        $cookies = is_array($_COOKIE) ? $_COOKIE : [];
        return array_intersect(array_keys($cookies), $this->conf['cookie']) ? true : false;
    }

}
