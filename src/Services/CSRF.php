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

use JDI\Exceptions\AppException;

/**
 * CSRF
 * @package JDI\Services
 */
class CSRF
{
    /**
     * @var int 过期时间(秒)
     */
    protected $expire;

    /**
     * @param array $conf 配置
     */
    public function __construct(array $conf)
    {
        $this->expire = $conf['expire'];
    }

    /**
     * 刷新令牌的过期时间
     * @return int|false 0表示不过期
     */
    public function refresh()
    {
        if (empty($_SESSION['csrf_token'])) {
            return false;
        }

        if (!$this->expire) {
            return 0;
        }

        return $_SESSION['csrf_token']['expire'] = time() + $this->expire;
    }

    /**
     * 获取 token
     * <p>会话初始化时才更新 token</p>
     * @return string
     */
    public function token()
    {
        if (empty($_SESSION['csrf_token'])) {
            $token = hash_pbkdf2('sha256', session_id(), uniqid(), 1e3);

            $_SESSION['csrf_token'] = [
                'token' => $token,
                'expire' => $this->expire ? time() + $this->expire : 0,
            ];
        } else {
            // 每次获取令牌时都刷新过期时间
            $this->refresh();

            $token = $_SESSION['csrf_token']['token'];
        }

        return $token;
    }

    /**
     * 生成带有 token 的表单域 html 元素
     * @return string
     */
    public function field()
    {
        $token = $this->token();
        return '<input type="hidden" name="_token" value="' . $token . '">';
    }

    /**
     * 校验 token
     * @return true
     * @throws AppException
     */
    public function check()
    {
        if (isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
            $token = $_SERVER['HTTP_X_CSRF_TOKEN'];
        } elseif (isset($_SERVER['REQUEST_METHOD']) && strtoupper($_SERVER['REQUEST_METHOD']) == 'POST') {
            $token = $_POST['_token'] ?? '';
        } elseif (isset($_SERVER['REQUEST_METHOD']) && strtoupper($_SERVER['REQUEST_METHOD']) == 'GET') {
            $token = $_GET['_token'] ?? '';
        } else {
            $token = $_REQUEST['_token'] ?? '';
        }

        if (!$token) {
            AppException::panic(10001001);
        }

        if (empty($_SESSION['csrf_token'])) {
            AppException::panic(10001002);
        }

        if ($_SESSION['csrf_token']['expire'] && (time() > $_SESSION['csrf_token']['expire'])) {
            AppException::panic(10001002);
        }

        if ($token != $_SESSION['csrf_token']['token']) {
            AppException::panic(10001001);
        }

        return true;
    }

}
