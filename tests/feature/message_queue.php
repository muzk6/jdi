<?php

/**
 * 异步队列
 */

require __DIR__ . '/../init.php';

// 生产
mq_publish('JDI_TEST', ['num' => 1]);
mq_publish('JDI_TEST', ['num' => 2]);
mq_publish('JDI_TEST', ['num' => 3]);

// 消费
mq_consume('JDI_TEST', function ($data) {
    var_dump($data);
});