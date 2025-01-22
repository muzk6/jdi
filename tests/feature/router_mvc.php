<?php

/**
 * 路由测试，经典MVC, 实现 Controller::action 模式
 * php -S 0.0.0.0:8080 router_mvc.php
 */

require __DIR__ . '/../init.php';

route_middleware(function () {
    echo '实现 Controller::action 模式<br>';
});

// url例子: /demo/foo
route_any('#^/(?<ct>[a-zA-Z_\d]+)/?(?<ac>[a-zA-Z_\d]+)?/?$#', function () {
    $matches = svc_router()->getREMatches();
    echo "Controller：{$matches['ct']}<br>";
    echo "Action：{$matches['ac']}<br>";
    echo '实例化看下面代码注释<br>';
    // $ctl = new $matches['ct']; // 实例化 Controller, 建议加上命名空间
    // return $ctl->$matches['ac'](); // 调用 action
});

route_dispatch();