<?php

/**
 * 语言缩写列表 https://blog.csdn.net/fenglailea/article/details/45888799
 * 建议规则：高4位固定，低4位以k位为分类
 * 10001xxxx 框架占用(请勿添加业务消息)，支持覆盖改写框架默认内容
 */

return [
    // 10001xxxx 框架占用(请勿添加业务消息)
    10001000 => '参数错误',
    10001001 => '非法请求',
    10001002 => '页面已过期，请先刷新',
    10001003 => '必填字段',
    10001004 => '请求失败',
    10001005 => '请先登录',
    10001006 => '请求太频繁，请于{time}重试',

    // 表单验证
    10001100 => '{name}不能为空',
    10001101 => '{name}必须为数字',
    10001102 => '{name}必须为数组',
    10001105 => '{name}必须为正确的邮箱地址',
    10001106 => '{name}必须为正确的URL格式',
    10001107 => '{name}必须为正确的IP地址',
    10001108 => '{name}必须为正确的时间戳格式',
    10001109 => '{name}必须为正确的日期格式',
    10001110 => '{name}格式不正确',
    10001111 => '{name}必须在{range}内',
    10001112 => '{name}必须不在{range}内',
    10001113 => '{name}必须在{left}-{right}范围内',
    10001114 => '{name}必须不在{left}-{right}范围内',
    10001115 => '{name}最大值为{max}',
    10001116 => '{name}最小值为{min}',
    10001117 => '{name}长度必须为{len}',
    10001118 => '{name}不一致',
    10001119 => '{name}必须大于{num}',
    10001120 => '{name}必须小于{num}',
    10001121 => '{name}必须大于或等于{num}',
    10001122 => '{name}必须小于或等于{num}',
    10001123 => '{name}必须等于{val}',

    // 业务
    10002000 => '账号或密码错误',
    10002001 => '欢迎 {name}',
];