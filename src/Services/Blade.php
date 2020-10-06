<?php


namespace JDI\Services;

use duncan3dc\Laravel\BladeInstance;

/**
 * 基于 Blade 封装的模板引擎
 * @package JDI\Services
 */
class Blade extends BladeInstance
{
    /**
     * @var array 需要 assign 的变量集合
     */
    protected $assign_vars = [];

    public function __construct(array $conf)
    {
        parent::__construct($conf['path_view'], $conf['path_cache'], $conf['directives']);

        if ($conf['no_cache'] && file_exists($conf['path_cache'])) {
            array_map('unlink', glob($conf['path_cache'] . '/*'));
        }
    }

    /**
     * 定义模板变量
     * @param string $name
     * @param mixed $value
     * @return Blade
     */
    public function assign(string $name, $value)
    {
        $this->assign_vars[$name] = $value;

        return $this;
    }

    /**
     * 渲染视图模板
     * @param string $view 模板名
     * @param array $params 模板里的参数
     * @return string
     */
    public function view(string $view, array $params = [])
    {
        $params = array_merge($this->assign_vars, $params);
        $this->assign_vars = [];

        return $this->render($view, array_merge($this->assign_vars, $params));
    }

}
