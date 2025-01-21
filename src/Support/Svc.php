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


use JDI\App;
use JDI\Services\Auth;
use JDI\Services\Blade;
use JDI\Services\BladeOne;
use JDI\Services\Config;
use JDI\Services\CSRF;
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
use PDO;
use Redis;

/**
 * 服务提供器
 * @package JDI\Support
 */
class Svc
{
    /**
     * 配置器
     * @return Config
     */
    public static function config()
    {
        return App::singleton(__METHOD__, function ($app) {
            return new Config([
                'path_config_first' => $app['config.path_config_first'], // 第一优先级配置目录
                'path_config_second' => $app['config.path_config_second'], // 第二优先级配置目录
                'path_config_third' => $app['config.path_config_third'], // 第三优先级配置目录，一般为库的默认配置目录
            ]);
        });
    }

    /**
     * 路由器
     * @return Router
     */
    public static function router()
    {
        return App::singleton(__METHOD__, function () {
            return new Router();
        });
    }

    /**
     * 请求参数
     * @return Request
     */
    public static function request()
    {
        return App::singleton(__METHOD__, function () {
            return new Request();
        });
    }

    /**
     * 日志
     * @return Log
     */
    public static function log()
    {
        return App::singleton(__METHOD__, function ($app) {
            $log = new Log();

            $path_data = $app['config.path_data'] . '/log'; // 日志路径
            $log->setFlushHandler(function ($logs) use ($path_data) {
                foreach ($logs as $v) {
                    $path = sprintf('%s/%s_%s.log', $path_data, $v['type'], date('Ym'));

                    $v = json_encode($v, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
                    file_put_contents($path, $v . PHP_EOL, FILE_APPEND);
                }
            });

            return $log;
        });
    }

    /**
     * 语言字典
     * @return Lang
     */
    public static function lang()
    {
        return App::singleton(__METHOD__, function () {
            if (isset($_COOKIE['lang']) && self::config()->exists('lang_' . $_COOKIE['lang'])) {
                $lang = $_COOKIE['lang'];
            } else {
                $lang = 'zh_CN';
            }

            return new Lang([
                'lang' => $lang, // 当前语言
                'dict' => [
                    // 中文字典
                    'zh_CN' => function () {
                        return self::config()->get('lang_zh_CN');
                    },
                    // 英文字典
                    'en' => function () {
                        return self::config()->get('lang_en');
                    },
                ]
            ]);
        });
    }

    /**
     * PDO - MySQL
     * @return PDOEngine
     */
    public static function mysql()
    {
        return App::singleton(__METHOD__, function () {
            return new PDOEngine(self::config()->get('mysql'), function ($host) {
                return new PDO($host['dsn'], $host['user'], $host['passwd'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            });
        });
    }

    /**
     * 模板引擎
     * @return Blade
     */
    public static function blade()
    {
        return App::singleton(__METHOD__, function ($app) {
            return new Blade([
                'path_view' => $app['config.path_view'],
                'path_cache' => $app['config.path_data'] . '/view_cache',
                'mode' => $app['config.debug'] ? BladeOne::MODE_DEBUG : BladeOne::MODE_AUTO,
            ]);
        });
    }

    /**
     * Redis
     * @return Redis
     */
    public static function redis()
    {
        return App::singleton(__METHOD__, function () {
            if (!extension_loaded('redis')) {
                trigger_error('"pecl install redis" at first', E_USER_ERROR);
            }

            $inst = new Redis();

            $conf = self::config()->get('redis');
            shuffle($conf);

            foreach ($conf as $host) {
                try {
                    if ($inst->pconnect($host['host'], $host['port'], $host['timeout'])) {
                        $inst->setOption(Redis::OPT_PREFIX, $host['prefix']);
                        $inst->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP);

                        break;
                    }
                } catch (\Exception $exception) {
                    trigger_error($exception->getMessage() . ': ' . json_encode($host, JSON_UNESCAPED_SLASHES), E_USER_WARNING);
                }
            }

            return $inst;
        });
    }

    /**
     * CURL
     * @return CURL
     */
    public static function curl()
    {
        return App::singleton(__METHOD__, function () {
            return new CURL();
        });
    }

    /**
     * CSRF
     * @return CSRF
     */
    public static function csrf()
    {
        return App::singleton(__METHOD__, function () {
            return new CSRF(['expire' => 0]);
        });
    }

    /**
     * 登录信息
     * @return Auth
     */
    public static function auth()
    {
        return App::singleton(__METHOD__, function () {
            $http_host = md5($_SERVER['HTTP_HOST'] ?? '');
            return new Auth(['prefix' => "AUTH:{$http_host}:"]);
        });
    }

    /**
     * 闪存
     * @return Flash
     */
    public static function flash()
    {
        return App::singleton(__METHOD__, function () {
            $http_host = md5($_SERVER['HTTP_HOST'] ?? '');
            return new Flash(['prefix' => "FLASH:{$http_host}:"]);
        });
    }

    /**
     * RabbitMQ
     * @return MessageQueue
     */
    public static function rabbitmq()
    {
        return App::singleton(__METHOD__, function () {
            return new MessageQueue(self::config()->get('rabbitmq'), function ($host) {
                return new \PhpAmqpLib\Connection\AMQPStreamConnection($host['host'], $host['port'], $host['user'], $host['passwd']);
            });
        });
    }

    /**
     * 白名单 开发环境跳过
     * @return Whitelist
     */
    public static function whitelist()
    {
        return App::singleton(__METHOD__, function () {
            return new Whitelist(self::config()->get('whitelist'));
        });
    }

    /**
     * Xdebug
     * @return Xdebug
     */
    public static function xdebug()
    {
        return App::singleton(__METHOD__, function ($app) {
            return new Xdebug([
                'path_data' => $app['config.path_data'] . '/xdebug_trace',
                'debug' => $app['config.debug'], // true 时跳过白名单限制
            ]);
        });
    }

    /**
     * XHProf
     * @return XHProf
     */
    public static function xhprof()
    {
        return App::singleton(__METHOD__, function ($app) {
            $conf = self::config()->get('xhprof');
            $conf['path_data'] = $app['config.path_data'] . '/xhprof';

            return new XHProf($conf);
        });
    }

}
