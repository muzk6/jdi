<?php

/**
 * Redis 缓存
 */

return [
    [
        'host' => 'redis',
        'port' => 6379,
        'prefix' => 'JDI:',
        'timeout' => 0.0, // 连接超时，单位秒，0表示没限制
    ],
];