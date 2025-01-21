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

    route_group(function () {

        /**
         * 主页
         */
        route_get('#^/demo(/index)?$#', function () {
            $title = input('title', 'JDI Demo');

            assign('first_name', 'Hello'); // 定义模板变量
            assign('last_name', 'JDI');
            assign('userId', svc_auth()->getUserId());

            return view('demo', ['title' => $title]); // 也可以在这里定义模板变量
        });

        // 此中间件和上面主页路由放在同一分组里，是后者路由的专属后置中间件
        route_middleware(function () {
            $content = svc_router()->getResponseContent();
            $content .= "<br><br>在后置中间件附加的内容";
            svc_router()->setResponseContent($content);
        });
    });

    /**
     * 同步请求
     */
    route_post('/demo/doc', function () {
        csrf_check();

        // 部分验证，一个一个获取
        $first_name = input('first_name');
        $last_name = validate('last_name')->required()->get('名字');

        // 部分验证，全部获取
        $request = request();

        // alert() 用例
        alert(json_encode(['first_name' => $first_name, 'last_name' => $last_name, 'request' => $request]));
    }, function (Exception $exception) {
        alert($exception->getMessage());
    });

    /**
     * 异步请求
     */
    route_post('/demo/xhr', function () {
        csrf_check();

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
        csrf_check();

        $user_id = validate('user_id:i')->gt(0)->get('用户ID ');

        svc_auth()->login($user_id);
        flash_set('msg', '登录成功'); // flash_set() 用例，配合 302 跳转，在页面用 flash_get() 取提示内容

        redirect('/demo');
    }, 'catch_doc');

    /**
     * 注销
     */
    route_post('/demo/logout', function () {
        csrf_check();

        svc_auth()->logout();
        flash_set('msg', '注销成功');

        redirect('/demo');
    }, 'catch_doc');
});

route_middleware(function () {
    log_push('access', [
        '__POST' => $_POST,
        'route' => svc_router()->getMatchedRoute(),
    ], 'access');
});

svc_router()->dispatch();

/**
 * DOC 请求异常回调
 * @param Exception $exception
 */
function catch_doc(Exception $exception)
{
    if ($exception instanceof AppException) {
        flash_set('msg', $exception->getMessage());
    }

    back();
}