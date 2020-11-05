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
     * 异常
     * @var \Exception
     */
    protected $exception;

    /**
     * @var callable 响应 404 的回调函数
     */
    protected $status_404_handler;

    /**
     * @var mixed 响应内容
     */
    private $response_content = '';

    /**
     * @var array 重复注册的路由
     */
    private $duplicate_route = [];

    /**
     * @var bool 是否已执行分发
     */
    private $is_dispatch = false;

    /**
     * 添加路由
     * @param string $method
     * @param string $url '/demo' 全匹配；'#/demo#' 正则匹配
     * @param callable $action
     * @param array $opts url_type: URL 匹配模式; regexp: 正则匹配; normal: 默认，全匹配;<br>
     * catch: 捕获异常后的回调; 默认只捕获 AppException 异常并返回 state:false 的 JSON
     */
    public function addRoute(string $method, $url, callable $action, array $opts = [])
    {
        if (empty($url)) {
            trigger_error('URL 不能为空: ' . json_encode(['method' => $method, 'url' => $url], JSON_UNESCAPED_SLASHES), E_USER_WARNING);
            return;
        }

        $url_type = $opts['url_type'] ?? 'normal';
        $catch = $opts['catch'] ?? null;

        // 没有显式指定 url_type=regexp 的情况下，# 开头的自动切换为正则模式
        $is_regexp = $url_type == 'regexp';
        if (!$is_regexp && $url[0] === '#') {
            $is_regexp = true;
        }

        if (!$is_regexp && $url !== '/') {
            $url = rtrim($url, '/');
        }

        $method = strtoupper($method);
        $hash = md5("{$method}_{$url}_{$is_regexp}");

        if (isset($this->duplicate_route[$hash])) {
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
            'catch' => $catch,
        ];
        $this->duplicate_route[$hash] = 1;
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
        if ($this->is_dispatch) {
            // 防止重复执行
            return;
        } else {
            $this->is_dispatch = true;
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

                    // 路由后置勾子，register_shutdown_function 兼容开发者业务逻辑里 exit
                    register_shutdown_function(function () use ($route_index, $route_value) {
                        $this->runMiddleware(array_slice($this->routes, $route_index + 1), $route_value['group'][0]);

                        $response_content = $this->getResponseContent();
                        if (is_array($response_content)) {
                            echo Utils::api_json(true, $response_content);
                        } elseif ($response_content instanceof AppException) {
                            echo Utils::api_json($response_content);
                        } elseif ($response_content) {
                            echo strval($response_content);
                        }
                    });

                    Svc::xhprof()->auto();
                    Svc::xdebug()->auto();

                    try {
                        // 路由前置勾子
                        $this->runMiddleware(array_slice($this->routes, 0, $route_index), $route_value['group'][0]);

                        // 设置响应内容，所有中间件执行完后再 echo
                        $this->setResponseContent(call_user_func($route_value['action']));
                    } catch (\Exception $exception) {
                        $this->exception = $exception;

                        if (is_callable($route_value['catch'])) {
                            $this->setResponseContent(call_user_func($route_value['catch'], $exception));
                        } else {
                            $this->setResponseContent($exception);
                        }
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
     * 异常，通常用于在后置中间件做处理
     * @return \Exception|null
     */
    public function getException()
    {
        return $this->exception;
    }

    /**
     * 设置响应内容
     * @param $response_content
     * @return $this
     */
    public function setResponseContent($response_content)
    {
        $this->response_content = $response_content;
        return $this;
    }

    /**
     * 获取响应内容
     * @return mixed
     */
    public function getResponseContent()
    {
        return $this->response_content;
    }

}
