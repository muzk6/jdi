<?php


namespace JDI\Services;

/**
 * 配置
 * @package JDI\Services
 */
class Config
{
    /**
     * @var array 配置文件集合
     */
    protected $config = [];

    /**
     * @var string 第一优先级配置目录，找不到配置文件时，就在第二优先级配置目录里找
     */
    protected $path_config_first;

    /**
     * @var string 第二优先级配置目录
     */
    protected $path_config_second;

    /**
     * Config constructor.
     * @param array $conf
     */
    public function __construct(array $conf)
    {
        $this->path_config_first = $conf['path_config_first'];
        $this->path_config_second = $conf['path_config_second'];
    }

    /**
     * 配置文件是否存在
     * @param string $filename
     * @return bool
     */
    public function exists(string $filename)
    {
        $config = &$this->config[$filename];
        if (!isset($config)) {
            if (is_file($path = "{$this->path_config_first}/{$filename}.php")) {
                $config = include($path);
            } else if ($this->path_config_second && is_file($path = "{$this->path_config_second}/{$filename}.php")) {
                $config = include($path);
            } else {
                return false;
            }
        }

        return true;
    }

    /**
     * 从配置文件读取配置
     * <p>优先从当前环境目录搜索配置文件</p>
     * @param string $key 配置文件.配置项0.配置项1
     * @return mixed
     */
    public function get(string $key)
    {
        $keys = explode('.', $key);
        $filename = array_shift($keys);

        if (!$this->exists($filename)) {
            trigger_error("{$filename}.php 配置文件不存在", E_USER_WARNING);
            return '';
        }


        $value = $this->config[$filename];
        foreach ($keys as $item) {
            if (!isset($value[$item])) {
                trigger_error("配置项 {$key} 不存在", E_USER_WARNING);
                return '';
            }

            $value = $value[$item];
        }

        return $value;
    }

    /**
     * 设置、覆盖 runtime 配置
     * @param string $key
     * @param $value
     * @return bool
     */
    public function set(string $key, $value)
    {
        $keys = explode('.', $key);
        $filename = array_shift($keys);

        $config = &$this->config[$filename];
        if (!isset($config)) {
            $config = [];
        }

        $ref = &$config;
        foreach ($keys as $item) {
            if (!isset($ref[$item])) {
                $ref[$item] = [];
            }

            $ref = &$ref[$item];
        }

        $ref = $value;
        return true;
    }

}
