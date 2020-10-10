<?php

/**
 * 简单路由
 * php -S 0.0.0.0:8080 router_simple.php
 */

use JDI\App;
use JDI\Exceptions\AppException;

require __DIR__ . '/../init.php';

/**
 * 主页
 */
route_get('/', function () {
    return 'Just Do It!<br><a href="/demo">>>>查看示例</a>';
});

/**
 * 路由组，Demo 示例
 */
route_group(function () {
    route_middleware(function () {
        if (!App::get('config.debug')) {
            http_response_code(404);
            exit;
        }
    });

    /**
     * 主页
     */
    route_get_re('#^/demo(/index)?$#', function () {
        $title = input('get.title', 'JDI Demo');

        assign('first_name', 'Hello'); // 定义模板变量
        assign('last_name', 'JDI');
        assign('userId', svc_auth()->getUserId());

        return view('demo', ['title' => $title]); // 也可以在这里定义模板变量
    });

    /**
     * 同步请求
     */
    route_post('/demo/doc', function () {
        try {
            xsrf_check();

            // 部分验证，一个一个获取
            $first_name = input('post.first_name');
            $last_name = validate('last_name')->required()->get('名字');

            // 部分验证，全部获取
            $request = request();

            flash_set('data', ['first_name' => $first_name, 'last_name' => $last_name, 'request' => $request]);
        } catch (AppException $app_exception) {
            flash_set('msg', $app_exception->getMessage());
            flash_set('data', $app_exception->getData());
        }

        back();
    });

    /**
     * 异步请求
     */
    route_post('/demo/xhr', function () {
        xsrf_check();

        validate('post.first_name')->required();
        validate('last_name')->required()->setTitle('名字');
        $request = request(true); // 以并联方式验证

        return [
            'request' => $request,
            'user_id' => svc_auth()->getUserId(), // 获取登录后的 userId
        ];
    });

    /**
     * 登录
     */
    route_post('/demo/login', function () {
        try {
            xsrf_check();

            $user_id = validate('user_id:i')->gt(0)->get('用户ID ');

            svc_auth()->login($user_id);
            flash_set('msg', '登录成功');

            redirect('/demo');
        } catch (AppException $app_exception) {
            flash_set('msg', $app_exception->getMessage());
            back();
        }
    });

    /**
     * 注销
     */
    route_post('/demo/logout', function () {
        try {
            xsrf_check();

            svc_auth()->logout();
            flash_set('msg', '注销成功');

            redirect('/demo');
        } catch (AppException $app_exception) {
            flash_set('msg', $app_exception->getMessage());
            back();
        }
    });
});

route_middleware(function () {
    logfile('access', [
        '__POST' => $_POST,
        'route' => svc_router()->getMatchedRoute(),
    ], 'access');
});

svc_router()->dispatch();