<?php


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
     * AppAuth constructor.
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->prefix = $config['prefix'];
    }

    /**
     * 登录
     * @param int|string $user_id 用户ID
     */
    public function login(string $user_id)
    {
        $_SESSION[$this->prefix . 'user_id'] = $user_id;
    }

    /**
     * 登出
     */
    public function logout()
    {
        unset($_SESSION[$this->prefix . 'user_id']);
    }

    /**
     * 用户ID
     * @return int|string
     */
    public function getUserId()
    {
        if (isset($_SESSION[$this->prefix . 'user_id'])) {
            if (is_numeric($_SESSION[$this->prefix . 'user_id'])) {
                return intval($_SESSION[$this->prefix . 'user_id']);
            } else {
                return $_SESSION[$this->prefix . 'user_id'];
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
        return !empty($_SESSION[$this->prefix . 'user_id']);
    }
}
