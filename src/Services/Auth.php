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

/**
 * 登录信息
 * @package JDI\Services
 */
class Auth
{
    /**
     * @var string 缓存键前缀
     */
    protected $prefix;

    /**
     * @var array 缓存容器
     */
    protected $bucket;

    /**
     * @var array 虚拟容器
     */
    protected $virtual_bucket = [];

    /**
     * AppAuth constructor.
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->prefix = $config['prefix'];
        $this->bucket = &$_SESSION;
    }

    /**
     * 设置模拟模式
     * @param bool $open
     * @return void
     */
    public function simulateMode(bool $open)
    {
        if ($open) {
            $this->bucket = &$this->virtual_bucket;
        } else {
            $this->bucket = &$_SESSION;
        }
    }

    /**
     * 登录
     * @param int|string $user_id 用户ID
     */
    public function login(string $user_id)
    {
        $this->bucket[$this->prefix . 'user_id'] = $user_id;
    }

    /**
     * 登出
     */
    public function logout()
    {
        unset($this->bucket[$this->prefix . 'user_id']);
    }

    /**
     * 用户ID
     * @return int|string
     */
    public function getUserId()
    {
        if (isset($this->bucket[$this->prefix . 'user_id'])) {
            if (is_numeric($this->bucket[$this->prefix . 'user_id'])) {
                return intval($this->bucket[$this->prefix . 'user_id']);
            } else {
                return $this->bucket[$this->prefix . 'user_id'];
            }
        } else {
            return 0;
        }
    }

    /**
     * 是否已登录
     * @return bool
     */
    public function isLogin()
    {
        return !empty($this->bucket[$this->prefix . 'user_id']);
    }
}
