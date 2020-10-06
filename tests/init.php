<?php

use JDI\App;

$path_root = realpath(__DIR__ . '/../');

require $path_root . '/vendor/autoload.php';

App::init([
    'config.app_env' => 'dev', // 当前环境，dev 会回显错误信息
    'config.path_config_first' => $path_root . '/config/dev', // 第一优先级配置目录，找不到配置文件时，就在第二优先级配置目录里找
    'config.path_config_second' => $path_root . '/config/common', // 第二优先级配置目录
    'config.path_data' => $path_root . '/data', // 数据目录
    'config.path_view' => $path_root . '/views', // 视图模板目录
    'config.init_handler' => null, // 容器初始化回调，为空时默认调用 \JDI\App::initHandler
]);