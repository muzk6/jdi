<?php

/**
 * 路由测试，中间件与路由
 * php -S 0.0.0.0:8080 router_advanced.php
 * 测试路径：/, /index, /foo, /bar
 */

require __DIR__ . '/../init.php';

route_middleware(function () {
    echo '根组 前置中间件1<br>';
});

route_middleware(function () {
    echo '根组 前置中间件2<br>';
});

// 子组A
route_group(function () {
    route_middleware(function () {
        echo '子组A 前置中间件1<br>';
    });

    route_middleware(function () {
        echo '子组A 前置中间件2<br>';
    });

    // url: /, /index
    route_get_re('#^/(index)?$#', function () {
        echo '主页<br>';
        echo '匹配路由：' . json_encode(svc_router()->getMatchedRoute(), JSON_UNESCAPED_SLASHES) . '<br>';
        echo '正则捕获：' . json_encode(svc_router()->getREMatches()) . '<br>';

        //子组A 前置中间件1
        //子组A 前置中间件2
        //根组 前置中间件1
        //根组 前置中间件2
        //主页
        //匹配路由：{"method":"GET","url":"#^/(index)?$#","is_regexp":true}
        //正则捕获：["\/"]
        //子组A 后置中间件1
        //子组A 后置中间件2
        //根组 后置中间件1
        //异常: 无
        //根组 后置中间件2
    });

    route_middleware(function () {
        echo '子组A 后置中间件1<br>';
    });

    route_middleware(function () {
        echo '子组A 后置中间件2<br>';
    });
});

// 子组B
route_group(function () {
    route_middleware(function () {
        echo '子组B 前置中间件1<br>';
    });

    // url: /foo
    route_get('/foo', function () {
        echo '匹配路由：' . json_encode(svc_router()->getMatchedRoute(), JSON_UNESCAPED_SLASHES) . '<br>';
        echo 'foo<br>';

        //子组B 前置中间件1
        //根组 前置中间件1
        //根组 前置中间件2
        //匹配路由：{"method":"GET","url":"/foo","is_regexp":false}
        //foo
        //子组B 后置中间件1
        //根组 后置中间件1
        //异常: 无
        //根组 后置中间件2
    });

    route_middleware(function () {
        echo '子组B 后置中间件1<br>';
    });
});

// 子组C
route_group(function () {
    route_middleware(function () {
        echo '子组C 前置中间件1，抛出异常，或者 exit，跳过后面的所有前置中间件和路由回调，但不影响后置中间件<br>';
        panic('抛出 AppException');
    });

    route_middleware(function () {
        echo '子组C 前置中间件2<br>';
    });

    // url: /bar
    route_get('/bar', function () {
        echo 'bar<br>';

        //子组C 前置中间件1，抛出异常，或者 exit，跳过后面的所有前置中间件和路由回调，但不影响后置中间件
        //{"s":false,"c":0,"m":"\u629b\u51fa AppException","d":{}}
        //子组C 后置中间件1
        //根组 后置中间件1
        //异常: 有
        //根组 后置中间件2
    });

    route_middleware(function () {
        echo '<br>子组C 后置中间件1<br>';
    });
});

route_middleware(function () {
    echo '根组 后置中间件1<br>';
    echo '异常: ' . (svc_router()->getAppException() ? '有' : '无') . '<br>';
});

route_middleware(function () {
    echo '根组 后置中间件2<br>';
});

svc_router()->setStatus404Handler(function () {
    echo '自定义404';
    http_response_code(404);
});

svc_router()->dispatch();