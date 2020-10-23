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
 * 基于 BladeOne 封装的模板引擎
 * @package JDI\Services
 */
class Blade extends BladeOne
{
    /**
     * @var array 需要 assign 的变量集合
     */
    protected $assign_vars = [];

    public function __construct(array $conf)
    {
        parent::__construct($conf['path_view'], $conf['path_cache'], $conf['mode']);
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
     * @throws \Exception
     */
    public function view(string $view, array $params = [])
    {
        $params = array_merge($this->assign_vars, $params);
        $this->assign_vars = [];

        return $this->run($view, array_merge($this->assign_vars, $params));
    }

}
