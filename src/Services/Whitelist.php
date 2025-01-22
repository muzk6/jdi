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
 * 白名单
 * @package JDI\Services
 */
class Whitelist
{
    /**
     * 支持网络号位数格式，例如 0.0.0.0/0 表示所有 IP
     * @var array
     */
    protected $ip_list;

    /**
     * 请求时带上白名单 Cookie. 判断逻辑为 isset($_COOKIE[...])
     * @var array
     */
    protected $cookie_list;

    /**
     * 白名单用户ID
     * @var array
     */
    protected $user_id_list;

    public function __construct(array $conf)
    {
        $this->ip_list = $conf['ip'];
        $this->cookie_list = $conf['cookie'];
        $this->user_id_list = $conf['user_id'];
    }

    /**
     * 当前 IP 是否在白名单内
     * @return bool
     */
    public function isSafeIp()
    {
        $client_ip_str = Utils::get_client_ip();
        $client_ip = ip2long($client_ip_str);

        foreach ($this->ip_list as $v) {

            if (strpos($v, '/') === false) {
                if ($v == $client_ip_str) {
                    return true;
                }

            } else {
                [$safe_ip_str, $subnet_num] = explode('/', $v);

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
    public function isSafeUserId($user_id)
    {
        return in_array($user_id, $this->user_id_list);
    }

    /**
     * 是否包含白名单安全 Cookie
     * @return bool
     */
    public function isSafeCookie()
    {
        $cookies = is_array($_COOKIE) ? $_COOKIE : [];
        return array_intersect(array_keys($cookies), $this->cookie_list) ? true : false;
    }

}
