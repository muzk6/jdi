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

use JDI\Exceptions\AppException;
use JDI\Support\Svc;
use JDI\Support\Utils;

/**
 * 路由注册器
 * @package JDI\Services
 */
class Router
{
    const TYPE_ROUTE = 1; // 路由
    const TYPE_MIDDLEWARE = 2; // 中间件

    /**
     * 路由
     * @var array
     */
    protected $routes = [];

    /**
     * 当前所属分组，支持嵌套
     * @var array
     */
    protected $group_stack = [];

    /**
     * 成功匹配的路由
     * @var array
     */
    protected $matched_route = [];

    /**
     * URL 正则捕获项
     * @var array
     */
    protected $re_matches = [];

    /**
     * 业务异常
     * @var AppException|null
     */
    protected $app_exception;

    /**
     * @var callable 响应 404 的回调函数
     */
    protected $status_404_handler;

    /**
     * 添加路由
     * @param string $method
     * @param string $url
     * @param callable $action
     * @param array $opts
     */
    public function addRoute(string $method, $url, callable $action, array $opts = [])
    {
        $is_regexp = isset($opts['url_type']) && $opts['url_type'] == 'regexp';
        if (!$is_regexp && $url !== '/') {
            $url = rtrim($url, '/');
        }

        $method = strtoupper($method);
        $hash = md5("{$method}_{$url}_{$is_regexp}");
        static $duplicate = [];

        if (isset($duplicate[$hash])) {
            trigger_error('路由重复注册: ' . json_encode(['method' => $method, 'url' => $url, 'is_regexp' => $is_regexp], JSON_UNESCAPED_SLASHES), E_USER_WARNING);
            return;
        }

        $this->routes[] = [
            'type' => self::TYPE_ROUTE, // 路由
            'group' => $this->getLastGroup(), // 所属分组
            'method' => $method,
            'url' => $url,
            'is_regexp' => $is_regexp,
            'action' => $action,
        ];
        $duplicate[$hash] = 1;
    }

    /**
     * 路由中间件
     * @param callable $fn
     */
    public function addMiddleware(callable $fn)
    {
        $this->routes[] = [
            'type' => self::TYPE_MIDDLEWARE, // 中间件
            'group' => $this->getLastGroup(), // 所属分组
            'fn' => $fn,
        ];
    }

    /**
     * 路由分组，隔离中间件
     * @param callable $fn
     */
    public function addGroup(callable $fn)
    {
        $this->group_stack[] = uniqid();
        call_user_func($fn);
        array_pop($this->group_stack);
    }

    /**
     * 当前分组与父分组
     * @return array [当前分组, 父分组]
     */
    protected function getLastGroup()
    {
        if (empty($this->group_stack)) {
            return ['', ''];
        }

        $count = count($this->group_stack);
        return [$this->group_stack[$count - 1], $this->group_stack[$count - 2] ?? ''];
    }

    /**
     * 路由分发
     */
    public function dispatch()
    {
        static $doing = false;
        if ($doing) { // 防止重复执行
            return;
        } else {
            $doing = true;
        }

        $request_url = parse_url(rawurldecode($_SERVER['REQUEST_URI']), PHP_URL_PATH);
        if ($request_url !== '/') {
            $request_url = rtrim($request_url, '/');
        }

        foreach ($this->routes as $route_index => $route_value) {
            $found = false;
            $method_allow = false;

            if ($route_value['type'] !== self::TYPE_ROUTE) {
                continue;
            }

            if ($route_value['is_regexp'] && preg_match($route_value['url'], $request_url, $this->re_matches)) {
                $found = true;
            } elseif ($route_value['url'] === $request_url) {
                $found = true;
            }

            if ($found) {
                $request_method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : '';

                if ($request_method === $route_value['method']) {
                    $method_allow = true;
                } elseif ($route_value['method'] === 'ANY') {
                    $method_allow = true;
                } elseif ($request_method === 'OPTIONS') {
                    return;
                }

                if ($method_allow) {
                    $this->matched_route = [
                        'method' => $route_value['method'],
                        'url' => $route_value['url'],
                        'is_regexp' => $route_value['is_regexp'],
                    ];

                    // 路由后置勾子，register_shutdown_function 防止开发者业务逻辑里 exit
                    register_shutdown_function(function () use ($route_index, $route_value) {
                        $this->runMiddleware(array_slice($this->routes, $route_index + 1), $route_value['group'][0]);
                    });

                    Svc::xhprof()->auto();
                    Svc::xdebug()->auto();

                    try {
                        // 路由前置勾子
                        $this->runMiddleware(array_slice($this->routes, 0, $route_index), $route_value['group'][0]);

                        $out = call_user_func($route_value['action']);
                        if (is_array($out)) {
                            echo Utils::api_json(true, $out);
                        } else {
                            echo strval($out);
                        }
                    } catch (AppException $app_exception) {
                        echo Utils::api_json($this->app_exception = $app_exception);
                    }

                    return;
                }
            }
        }

        $this->fireStatus404();
    }

    /**
     * 运行中间件
     * @param array $routes
     * @param string $group 空时为根分组
     * @return void
     * @throws AppException
     */
    protected function runMiddleware(array $routes, string $group)
    {
        if (empty($routes)) {
            return;
        }

        $next_routes = [];
        $parent_group = '';
        foreach ($routes as $v) {
            if ($v['type'] !== self::TYPE_MIDDLEWARE) {
                continue;
            }

            if ($v['group'][0] !== $group) {
                $next_routes[] = $v;
                continue;
            }

            $parent_group = $v['group'][1];
            call_user_func($v['fn']);
        }

        if ($parent_group !== $group) {
            $this->runMiddleware($next_routes, $parent_group);
        }
    }

    /**
     * 设置响应 404 的回调函数
     * @param callable $status_404_handler
     * @return $this
     */
    public function setStatus404Handler(callable $status_404_handler)
    {
        $this->status_404_handler = $status_404_handler;
        return $this;
    }

    /**
     * 触发 404 错误
     * @return mixed
     */
    public function fireStatus404()
    {
        http_response_code(404);

        if (is_callable($this->status_404_handler)) {
            echo call_user_func($this->status_404_handler);
        }

        exit;
    }

    /**
     * 成功匹配的路由
     * @return array
     */
    public function getMatchedRoute()
    {
        return $this->matched_route;
    }

    /**
     * URL 正则捕获项
     * @return array
     */
    public function getREMatches()
    {
        return $this->re_matches;
    }

    /**
     * 业务异常
     * @return AppException|null
     */
    public function getAppException()
    {
        return $this->app_exception;
    }

}
