<?php

/*
 * This file is part of the muzk6/jdi.
 *
 * (c) muzk6 <muzk6x@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use JDI\Services\Auth;
use JDI\Services\Blade;
use JDI\Services\Config;
use JDI\Services\CURL;
use JDI\Services\Flash;
use JDI\Services\Lang;
use JDI\Services\Log;
use JDI\Services\MessageQueue;
use JDI\Services\PDOEngine;
use JDI\Services\Request;
use JDI\Services\Router;
use JDI\Services\Whitelist;
use JDI\Services\Xdebug;
use JDI\Services\XHProf;
use JDI\Services\XSRF;
use JDI\Support\Svc;

if (!function_exists('svc_config')) {
    /**
     * 配置器
     * @return Config
     */
    function svc_config()
    {
        return Svc::config();
    }
}

if (!function_exists('svc_router')) {
    /**
     * 路由器
     * @return Router
     */
    function svc_router()
    {
        return Svc::router();
    }
}

if (!function_exists('svc_request')) {
    /**
     * 请求参数
     * @return Request
     */
    function svc_request()
    {
        return Svc::request();
    }
}

if (!function_exists('svc_log')) {
    /**
     * 日志
     * @return Log
     */
    function svc_log()
    {
        return Svc::log();
    }
}

if (!function_exists('svc_lang')) {
    /**
     * 语言字典
     * @return Lang
     */
    function svc_lang()
    {
        return Svc::lang();
    }
}

if (!function_exists('svc_mysql')) {
    /**
     * PDO - MySQL
     * @return PDOEngine
     */
    function svc_mysql()
    {
        return Svc::mysql();
    }
}

if (!function_exists('db')) {
    /**
     * Svc::mysql() 的别名
     * @return PDOEngine
     * @see Svc::mysql()
     */
    function db()
    {
        return Svc::mysql();
    }
}

if (!function_exists('svc_blade')) {
    /**
     * 模板引擎
     * @return Blade
     */
    function svc_blade()
    {
        return Svc::blade();
    }
}

if (!function_exists('svc_redis')) {
    /**
     * Redis
     * @return Redis
     */
    function svc_redis()
    {
        return Svc::redis();
    }
}

if (!function_exists('svc_curl')) {
    /**
     * CURL
     * @return CURL
     */
    function svc_curl()
    {
        return Svc::curl();
    }
}

if (!function_exists('svc_xsrf')) {
    /**
     * XSRF
     * @return XSRF
     */
    function svc_xsrf()
    {
        return Svc::xsrf();
    }
}

if (!function_exists('svc_auth')) {
    /**
     * 登录信息
     * @return Auth
     */
    function svc_auth()
    {
        return Svc::auth();
    }
}

if (!function_exists('svc_flash')) {
    /**
     * 闪存
     * @return Flash
     */
    function svc_flash()
    {
        return Svc::flash();
    }
}

if (!function_exists('svc_rabbitmq')) {
    /**
     * RabbitMQ
     * @return MessageQueue
     */
    function svc_rabbitmq()
    {
        return Svc::rabbitmq();
    }
}

if (!function_exists('svc_whitelist')) {
    /**
     * 白名单 开发环境跳过
     * @return Whitelist
     */
    function svc_whitelist()
    {
        return Svc::whitelist();
    }
}

if (!function_exists('svc_xdebug')) {
    /**
     * Xdebug
     * @return Xdebug
     */
    function svc_xdebug()
    {
        return Svc::xdebug();
    }
}

if (!function_exists('svc_xhprof')) {
    /**
     * XHProf
     * @return XHProf
     */
    function svc_xhprof()
    {
        return Svc::xhprof();
    }
}