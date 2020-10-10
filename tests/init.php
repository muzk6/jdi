<?php

use JDI\App;

$path_root = realpath(__DIR__ . '/../');

require $path_root . '/vendor/autoload.php';

App::init([
    'config.app_env' => 'dev', // 当前环境；dev 时回显错误信息
]);

$path_root = realpath(__DIR__); // 项目根目录
require $path_root . '/vendor/autoload.php';