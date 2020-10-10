<?php

use JDI\App;
use JDI\Services\Auth;
use JDI\Services\Blade;
use JDI\Services\BladeOne;
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

if (!function_exists('svc_config')) {
    /**
     * 配置器
     * @return Config
     */
    function svc_config()
    {
        return App::singleton('service.config', function ($app) {
            return new Config([
                'path_config_first' => $app['config.path_config_first'], // 第一优先级配置目录
                'path_config_second' => $app['config.path_config_second'], // 第二优先级配置目录
                'path_config_third' => $app['config.path_config_third'], // 第三优先级配置目录，一般为库的默认配置目录
            ]);
        });
    }
}

if (!function_exists('svc_router')) {
    /**
     * 路由器
     * @return Router
     */
    function svc_router()
    {
        return App::singleton('service.router', function () {
            return new Router();
        });
    }
}

if (!function_exists('svc_request')) {
    /**
     * 请求参数
     * @return Request
     */
    function svc_request()
    {
        return App::singleton('service.request', function () {
            return new Request();
        });
    }
}

if (!function_exists('svc_log')) {
    /**
     * 日志
     * @return Log
     */
    function svc_log()
    {
        return App::singleton('service.log', function ($app) {
            return new Log([
                'path_data' => $app['config.path_data'] . '/log', // 日志路径
            ]);
        });
    }
}

if (!function_exists('svc_lang')) {
    /**
     * 语言字典
     * @return Lang
     */
    function svc_lang()
    {
        return App::singleton('service.lang', function () {
            if (isset($_COOKIE['lang']) && svc_config()->exists('lang_' . $_COOKIE['lang'])) {
                $lang = $_COOKIE['lang'];
            } else {
                $lang = 'zh_CN';
            }

            return new Lang([
                'lang' => $lang, // 当前语言
                'dict' => [
                    // 中文字典
                    'zh_CN' => function () {
                        return svc_config()->get('lang_zh_CN');
                    },
                    // 英文字典
                    'en' => function () {
                        return svc_config()->get('lang_en');
                    },
                ]
            ]);
        });
    }
}

if (!function_exists('svc_mysql')) {
    /**
     * PDO - MySQL
     * @return PDOEngine
     */
    function svc_mysql()
    {
        return App::singleton('service.mysql', function () {
            return new PDOEngine(svc_config()->get('mysql'), function ($host) {
                return new PDO($host['dsn'], $host['user'], $host['passwd'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            });
        });
    }
}

if (!function_exists('db')) {
    /**
     * svc_mysql() 的别名
     * @return PDOEngine
     * @see svc_mysql()
     */
    function db()
    {
        return svc_mysql();
    }
}

if (!function_exists('svc_blade')) {
    /**
     * 模板引擎
     * @return Blade
     */
    function svc_blade()
    {
        return App::singleton('service.blade', function ($app) {
            return new Blade([
                'path_view' => $app['config.path_view'],
                'path_cache' => $app['config.path_data'] . '/view_cache',
                'mode' => $app['config.debug'] ? BladeOne::MODE_DEBUG : BladeOne::MODE_AUTO,
            ]);
        });
    }
}

if (!function_exists('svc_redis')) {
    /**
     * Redis
     * @return Redis
     */
    function svc_redis()
    {
        return App::singleton('service.redis', function () {
            if (!extension_loaded('redis')) {
                trigger_error('"pecl install redis" at first', E_USER_ERROR);
            }

            $inst = new Redis();

            $conf = svc_config()->get('redis');
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
}

if (!function_exists('svc_curl')) {
    /**
     * CURL
     * @return CURL
     */
    function svc_curl()
    {
        return App::singleton('service.curl', function () {
            return new CURL();
        });
    }
}

if (!function_exists('svc_xsrf')) {
    /**
     * XSRF
     * @return XSRF
     */
    function svc_xsrf()
    {
        return App::singleton('service.xsrf', function () {
            return new XSRF(['expire' => 0]);
        });
    }
}

if (!function_exists('svc_auth')) {
    /**
     * 登录信息
     * @return Auth
     */
    function svc_auth()
    {
        return App::singleton('service.auth', function () {
            $http_host = md5($_SERVER['HTTP_HOST'] ?? '');
            return new Auth(['prefix' => "AUTH:{$http_host}:"]);
        });
    }
}

if (!function_exists('svc_flash')) {
    /**
     * 闪存
     * @return Flash
     */
    function svc_flash()
    {
        return App::singleton('service.flash', function () {
            $http_host = md5($_SERVER['HTTP_HOST'] ?? '');
            return new Flash(['prefix' => "FLASH:{$http_host}:"]);
        });
    }
}

if (!function_exists('svc_rabbitmq')) {
    /**
     * RabbitMQ
     * @return MessageQueue
     */
    function svc_rabbitmq()
    {
        return App::singleton('service.rabbitmq', function () {
            return new MessageQueue(svc_config()->get('rabbitmq'), function ($host) {
                return new \PhpAmqpLib\Connection\AMQPStreamConnection($host['host'], $host['port'], $host['user'], $host['passwd']);
            });
        });
    }
}

if (!function_exists('svc_whitelist')) {
    /**
     * 白名单 开发环境跳过
     * @return Whitelist
     */
    function svc_whitelist()
    {
        return App::singleton('service.whitelist', function ($app) {
            return new Whitelist(svc_config()->get('whitelist'));
        });
    }
}

if (!function_exists('svc_xdebug')) {
    /**
     * Xdebug
     * @return Xdebug
     */
    function svc_xdebug()
    {
        return App::singleton('service.xdebug', function ($app) {
            return new Xdebug([
                'path_data' => $app['config.path_data'] . '/xdebug_trace',
                'debug' => $app['config.debug'], // true 时跳过白名单限制
            ]);
        });
    }
}

if (!function_exists('svc_xhprof')) {
    /**
     * XHProf
     * @return XHProf
     */
    function svc_xhprof()
    {
        return App::singleton('service.xhprof', function ($app) {
            if (svc_config()->exists('xhprof')) {
                $conf = svc_config()->get('xhprof');
            } else {
                $conf = [
                    'enable' => 0,
                    'probability' => 1, // 采样概率，1=100%
                    'min_time' => 0.1, // 最小耗时，单位秒，超时则记录否则跳过
                ];
            }

            $conf['path_data'] = $app['config.path_data'] . '/xhprof';

            return new XHProf($conf);
        });
    }
}