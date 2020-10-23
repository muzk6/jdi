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

use PDO;

/**
 * 分片后连接资源的 PDO
 * @package JDI\Services
 */
class PDOSharding
{
    /**
     * @var string 数据库区域
     */
    public $section = '';

    /**
     * @var string `库名`.`表名`
     */
    public $table;

    /**
     * @var PDOEngine 数据库对象
     */
    protected $pdo;

    /**
     * @var bool 日志开关
     */
    protected $open_log = false;

    /**
     * @param PDOEngine $pdo
     * @param string $table 原始表名
     * @param string $index 分表依据
     * @param callable $shard 分表逻辑的回调函数
     * @param bool $open_log
     */
    public function __construct(PDOEngine $pdo, string $table, string $index, callable $shard, $open_log = false)
    {
        $this->pdo = $pdo;
        $this->open_log = $open_log;

        $sharding = call_user_func_array($shard, [$table, $index]);
        $this->section = $sharding['section'] ?? '';

        if (!empty($sharding['dbname'])) {
            $this->table = "`{$sharding['dbname']}`.`{$sharding['table']}`";
        } else {
            $this->table = "`{$sharding['table']}`";
        }
    }

    /**
     * 获取连接资源 PDO
     * @param bool $use_master
     * @return PDO
     */
    public function getConnection(bool $use_master = false)
    {
        return $this->pdo->getConnection($use_master, $this->section);
    }

    /**
     * 开启事务，并返回连接资源
     * @return AppPDO
     */
    public function beginTransaction()
    {
        $app_pdo = new AppPDO($this->getConnection(true), $this->open_log, $this->table);
        $app_pdo->beginTransaction();

        return $app_pdo;
    }

    /**
     * 查询一行记录
     * @param string $sql 原生 sql 语句
     * @param array $binds 防注入的参数绑定
     * @param bool $use_master 是否使用主库
     * @return array|false 无记录时返回 false
     */
    public function getOne(string $sql, array $binds = [], bool $use_master = false)
    {
        return (new AppPDO($this->getConnection($use_master), $this->open_log))->getOne($sql, $binds);
    }

    /**
     * 查询多行记录
     * @param string $sql 原生 sql 语句
     * @param array $binds 防注入的参数绑定
     * @param bool $use_master 是否使用主库
     * @return array 无记录时返回空数组 []
     */
    public function getAll(string $sql, array $binds = [], bool $use_master = false)
    {
        return (new AppPDO($this->getConnection($use_master), $this->open_log))->getAll($sql, $binds);
    }

    /**
     * 执行 insert, replace, update, delete 等增删改 sql 语句
     * @param string $sql 原生 sql 语句
     * @param array $binds 防注入的参数绑定
     * @return int 执行 insert, replace 时返回最后插入的主键ID, 失败时返回0
     * <br>其它语句返回受影响行数，否则返回0(<b>注意：不要随便用来当判断条件</b>)
     */
    public function query(string $sql, array $binds = [])
    {
        return (new AppPDO($this->getConnection(true), $this->open_log))->query($sql, $binds);
    }

    /**
     * 查询一行记录
     * @param string $columns 查询字段
     * @param array|string $where 用法参考 \Core\AppPDO::parseWhere
     * @param string $table 表名，为空时自动切换为 $this->table
     * @param string $order_by ORDER BY 语法 e.g. 'id DESC'
     * @param bool $use_master 是否使用主库
     * @return array|false 无记录时返回 false
     */
    public function selectOne(string $columns, $where, string $table = '', string $order_by = '', bool $use_master = false)
    {
        return (new AppPDO($this->getConnection($use_master), $this->open_log))->selectOne($columns, $where, $table ?: $this->table, $order_by);
    }

    /**
     * 查询多行记录
     * @param string $columns 查询字段
     * @param array|string $where 用法参考 \Core\AppPDO::parseWhere
     * @param string $table 表名，为空时自动切换为 $this->table
     * @param string $order_by ORDER BY 语法 e.g. 'id DESC'
     * @param array $limit LIMIT 语法 e.g. LIMIT 25 即 [25]; LIMIT 0, 25 即 [0, 25]
     * @param bool $use_master 是否使用主库
     * @return array 无记录时返回空数组 []
     */
    public function selectAll(string $columns, $where, string $table = '', string $order_by = '', array $limit = [], bool $use_master = false)
    {
        return (new AppPDO($this->getConnection($use_master), $this->open_log))->selectAll($columns, $where, $table ?: $this->table, $order_by, $limit);
    }

    /**
     * 插入记录
     * @param array $data 要插入的数据 ['col0' => 1]
     * <p>支持参数绑定的方式 ['col0' => ['UNIX_TIMESTAMP()']]</p>
     * <p>支持单条 [...]; 或多条 [[...], [...]]</p>
     * @param string $table 表名，为空时自动切换为 $this->table
     * @param bool $ignore 是否使用 INSERT IGNORE 语法
     * @return int 返回最后插入的主键ID(批量插入时返回第1个ID), 失败时返回0
     */
    public function insert(array $data, string $table = '', $ignore = false)
    {
        return (new AppPDO($this->getConnection(true), $this->open_log))->insert($data, $table ?: $this->table, $ignore);
    }

    /**
     * 更新记录
     * @param array $data 要更新的字段 ['col0' => 1]
     * <p>支持参数绑定的方式 ['col0' => ['n+?', 1]]</p>
     * @param array|string $where 用法参考 \Core\AppPDO::parseWhere
     * @param string $table 表名，为空时自动切换为 $this->table
     * @return int 被更新的行数，否则返回0(<b>注意：不要随便用来当判断条件</b>)
     */
    public function update(array $data, $where, string $table = '')
    {
        return (new AppPDO($this->getConnection(true), $this->open_log))->update($data, $where, $table ?: $this->table);
    }

    /**
     * 删除记录
     * @param array|string $where 用法参考 \Core\AppPDO::parseWhere
     * @param string $table 表名，为空时自动切换为 $this->table
     * @return int 被删除的行数，否则返回0(<b>注意：不要随便用来当判断条件</b>)
     */
    public function delete($where, string $table = '')
    {
        return (new AppPDO($this->getConnection(true), $this->open_log))->delete($where, $table ?: $this->table);
    }

}
