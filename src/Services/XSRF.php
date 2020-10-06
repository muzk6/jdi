<?php

namespace JDI\Services;

use JDI\Exceptions\AppException;

/**
 * XSRF
 * @package JDI\Services
 */
class XSRF
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
        if (empty($_SESSION['xsrf_token'])) {
            return false;
        }

        if (!$this->expire) {
            return 0;
        }

        return $_SESSION['xsrf_token']['expire'] = time() + $this->expire;
    }

    /**
     * 获取 token
     * <p>会话初始化时才更新 token</p>
     * @return string
     */
    public function token()
    {
        if (empty($_SESSION['xsrf_token'])) {
            $token = hash_pbkdf2('sha256', session_id(), uniqid(), 1e3);

            $_SESSION['xsrf_token'] = [
                'token' => $token,
                'expire' => $this->expire ? time() + $this->expire : 0,
            ];
        } else {
            // 每次获取令牌时都刷新过期时间
            $this->refresh();

            $token = $_SESSION['xsrf_token']['token'];
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
        if (isset($_SERVER['HTTP_X_XSRF_TOKEN'])) {
            $token = $_SERVER['HTTP_X_XSRF_TOKEN'];
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

        if (empty($_SESSION['xsrf_token'])) {
            AppException::panic(10001002);
        }

        if ($_SESSION['xsrf_token']['expire'] && (time() > $_SESSION['xsrf_token']['expire'])) {
            AppException::panic(10001002);
        }

        if ($token != $_SESSION['xsrf_token']['token']) {
            AppException::panic(10001001);
        }

        return true;
    }

}
