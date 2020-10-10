<?php

namespace JDI;

use JDI\Exceptions\ErrorHandler;

class App implements \ArrayAccess
{
    /**
     * @var App $app ;
     */
    public static $app;

    protected $values = [];

    private function __construct()
    {
    }

    public function offsetSet($offset, $value)
    {
        $this->values[$offset] = $value;
    }

    public function offsetExists($offset)
    {
        return isset($this->values[$offset]);
    }

    public function offsetUnset($offset)
    {
        unset($this->values[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->values[$offset];
    }

    /**
     * 容器单例初始化
     * @param array $values
     * @return App
     */
    public static function init(array $values = [])
    {
        $app = &static::$app;
        if (!$app) {
            $app = new static();

            foreach ($values as $key => $value) {
                $app[$key] = $value;
            }

            isset($app['config.debug']) || $app['config.debug'] = true; // 调试开发模式
            isset($app['config.path_jdi']) || $app['config.path_jdi'] = realpath(__DIR__ . '/../'); // 框架根目录
            isset($app['config.path_data']) || $app['config.path_data'] = $app['config.path_jdi'] . '/data'; // 数据目录
            isset($app['config.path_view']) || $app['config.path_view'] = $app['config.path_jdi'] . '/views'; // 视图模板目录
            isset($app['config.path_config_first']) || $app['config.path_config_first'] = ''; // 第一优先级配置目录
            isset($app['config.path_config_second']) || $app['config.path_config_second'] = ''; // 第二优先级配置目录
            isset($app['config.path_config_third']) || $app['config.path_config_third'] = $app['config.path_jdi'] . '/config'; // 第三优先级配置目录

            if (isset($app['config.init_handler']) && is_callable($app['config.init_handler'])) {
                call_user_func($app['config.init_handler'], $app);
            } else {
                $app->initHandler($app);
            }
        }

        return $app;
    }

    /**
     * 默认初始化回调
     * @param App $app
     */
    protected function initHandler(App $app)
    {
        // session
        if (PHP_SAPI != 'cli') {
            $path_session = $app['config.path_data'] . '/session';
            ini_set('session.save_handler', 'files');
            ini_set('session.save_path', $path_session);
            ini_set('session.gc_maxlifetime', 1440); // session过期时间
            ini_set('session.cookie_lifetime', 0); // cookie过期时间，0表示浏览器重启后失效
            ini_set('session.name', 'user_session');
            ini_set('session.cookie_httponly', 'On');

            if (!file_exists($path_session)) {
                mkdir($path_session, 0744, true);
            }

            session_id() || session_start();
        }

        $path_log = $app['config.path_data'] . '/log';
        if (!file_exists($path_log)) {
            mkdir($path_log, 0744, true);
        }

        // PHP 标准错误处理程序，能记录 Fatal Error, Parse Error
        ini_set('log_errors', 1);
        ini_set('error_log', sprintf('%s/standard_%s.log', $path_log, date('Ym')));

        // 详细错误日志
        set_error_handler([ErrorHandler::class, 'errorHandler']);
        // 未捕获的详情异常日志
        set_exception_handler([ErrorHandler::class, 'exceptionHandler']);

        // 配置文件里的默认配置
        date_default_timezone_set('PRC');

        // 环境配置
        if ($app['config.debug']) {
            ini_set('opcache.enable', 0);
            ini_set('opcache.enable_cli', 0);

            error_reporting(E_ALL);
            $display = 1;
        } else {
            error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
            $display = 0;
        }

        if (PHP_SAPI == 'cli') {
            $display = 1;
        }

        // 错误回显
        ini_set('display_errors', $display);
    }

    /**
     * 容器获取
     * @param string $name
     * @return mixed
     */
    public static function get(string $name)
    {
        return static::$app[$name];
    }

    /**
     * 容器设置
     * @param string $name
     * @param $value
     */
    public static function set(string $name, $value)
    {
        static::$app[$name] = $value;
    }

    /**
     * 单例服务对象
     * @param string $name 单例键名
     * @param callable $fn 单例服务实例化回调
     * @return mixed
     */
    public static function singleton(string $name, callable $fn)
    {
        $app = static::$app;
        if (!isset($app[$name])) {
            $app[$name] = call_user_func($fn, $app);
        }

        return $app[$name];
    }
}