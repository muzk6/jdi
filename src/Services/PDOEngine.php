<?php

namespace JDI\Services;

use Exception;
use PDO;

/**
 * PDO 引擎
 * @package JDI\Services
 */
class PDOEngine
{
    /**
     * @var array 数据库配置
     */
    protected $conf;

    /**
     * @var array 所有分区的连接对象集合
     */
    protected $section_conn = [];

    /**
     * @var callable 服务实例化回调
     */
    protected $connection_handler;

    /**
     * @param array $conf 数据库配置
     * @param callable $connection_handler 服务实例化回调
     */
    public function __construct(array $conf, callable $connection_handler)
    {
        $this->conf = $conf;
        $this->connection_handler = $connection_handler;
    }

    public function __destruct()
    {
        $this->close();
    }

    /**
     * 获取连接资源 PDO
     * @param bool $use_master 是否使用主库
     * @param string $section 数据库区域，为空时自动切换为 default
     * @return PDO
     */
    public function getConnection(bool $use_master = false, string $section = '')
    {
        if (empty($section)) {
            $section = 'default';
        }

        $section_conf = &$this->conf['sections'][$section];
        if (is_null($section_conf)) {
            trigger_error("数据库配置错误，`sections -> {$section}` 不存在", E_USER_ERROR);
        }

        if ($use_master || empty($section_conf['slaves'])) { // 主库；在没有配置从库时也使用主库
            $connection = &$this->section_conn[$section]['master'];
            if (empty($connection)) {
                $connection = call_user_func($this->connection_handler, $section_conf['master']);
            }

        } else { // 从库
            $connection = &$this->section_conn[$section]['slave'];
            if (empty($connection)) {
                shuffle($section_conf['slaves']);
                foreach ($section_conf['slaves'] as $slave) {
                    try {
                        $connection = call_user_func($this->connection_handler, $slave);
                        break;
                    } catch (Exception $exception) {
                        trigger_error($exception->getMessage() . ': ' . json_encode($slave, JSON_UNESCAPED_SLASHES), E_USER_WARNING);
                    }
                }
            }
        }

        return $connection;
    }

    /**
     * 关闭所有连接资源
     * @return void
     */
    public function close()
    {
        foreach ($this->section_conn as $section => &$connections) {
            foreach ($connections as &$connection) {
                $connection = null;
            }
        }
    }

    /**
     * 开启事务，并返回连接资源
     * @param string $section 数据库区域，为空时自动切换为 default
     * @return AppPDO
     */
    public function beginTransaction(string $section = '')
    {
        $app_pdo = new AppPDO($this->getConnection(true, $section), $this->conf['log']);
        $app_pdo->beginTransaction();

        return $app_pdo;
    }

    /**
     * 分表
     * @param string $table 原始表名
     * @param string $index 分表依据
     * @return PDOSharding
     */
    public function shard(string $table, string $index)
    {
        return new PDOSharding($this, $table, $index, $this->conf['sharding'], $this->conf['log']);
    }

    /**
     * 解析 WHERE 语句，转为如下格式
     * <p>有条件: ['col0=?', [1]]</p>
     * <p>无条件: ['', []]</p>
     * @param array|string $where
     * <p>$where = ['name' => 'sparrow', 'order' => 1]</p>
     * <p>$where = 'id=1'</p>
     * <p>$where = ''</p>
     * <p>$where = ['id=?', 1]</p>
     * <p>或者 $where = ['id=?', [1]]</p>
     * <p>$where = [ ['and id=?', 1], ['or `order`=?', 2] ]</p>
     * <p>$where = [ ['and name=?', 's'] ]</p>
     * <p>或者指定 key(用于覆盖同 key 条件) $where = [ 'name' => ['and name=?', 's'] ]</p>
     * @return array
     */
    public function parseWhere($where)
    {
        return (new AppPDO(null, false))->parseWhere($where);
    }

    /**
     * 查询一行记录
     * @param string $sql 原生 sql 语句
     * @param array $binds 防注入的参数绑定
     * @param bool $use_master 是否使用主库
     * @param string $section 数据库区域，为空时自动切换为 default
     * @return array|false 无记录时返回 false
     */
    public function getOne(string $sql, array $binds = [], bool $use_master = false, string $section = '')
    {
        return (new AppPDO($this->getConnection($use_master, $section), $this->conf['log']))->getOne($sql, $binds);
    }

    /**
     * 查询多行记录
     * @param string $sql 原生 sql 语句
     * @param array $binds 防注入的参数绑定
     * @param bool $use_master 是否使用主库
     * @param string $section 数据库区域，为空时自动切换为 default
     * @return array 无记录时返回空数组 []
     */
    public function getAll(string $sql, array $binds = [], bool $use_master = false, string $section = '')
    {
        return (new AppPDO($this->getConnection($use_master, $section), $this->conf['log']))->getAll($sql, $binds);
    }

    /**
     * 执行 insert, replace, update, delete 等增删改 sql 语句
     * @param string $sql 原生 sql 语句
     * @param array $binds 防注入的参数绑定
     * @param string $section 数据库区域，为空时自动切换为 default
     * @return int 执行 insert, replace 时返回最后插入的主键ID, 失败时返回0
     * <br>其它语句返回受影响行数，否则返回0(<b>注意：不要随便用来当判断条件</b>)
     */
    public function query(string $sql, array $binds = [], string $section = '')
    {
        return (new AppPDO($this->getConnection(true, $section), $this->conf['log']))->query($sql, $binds);
    }

    /**
     * 查询一行记录
     * @param string $columns 查询字段
     * @param array|string $where 用法参考 \Core\AppPDO::parseWhere
     * @param string $table 表名
     * @param string $order_by ORDER BY 语法 e.g. 'id DESC'
     * @param bool $use_master 是否使用主库
     * @param string $section 数据库区域，为空时自动切换为 default
     * @return array|false 无记录时返回 false
     */
    public function selectOne(string $columns, $where, string $table, string $order_by = '', bool $use_master = false, string $section = '')
    {
        return (new AppPDO($this->getConnection($use_master, $section), $this->conf['log']))->selectOne($columns, $where, $table, $order_by);
    }

    /**
     * 查询多行记录
     * @param string $columns 查询字段
     * @param array|string $where 用法参考 \Core\AppPDO::parseWhere
     * @param string $table 表名
     * @param string $order_by ORDER BY 语法 e.g. 'id DESC'
     * @param array $limit LIMIT 语法 e.g. LIMIT 25 即 [25]; LIMIT 0, 25 即 [0, 25]
     * @param bool $use_master 是否使用主库
     * @param string $section 数据库区域，为空时自动切换为 default
     * @return array 无记录时返回空数组 []
     */
    public function selectAll(string $columns, $where, string $table, string $order_by = '', array $limit = [], bool $use_master = false, string $section = '')
    {
        return (new AppPDO($this->getConnection($use_master, $section), $this->conf['log']))->selectAll($columns, $where, $table, $order_by, $limit);
    }

    /**
     * 插入记录
     * @param array $data 要插入的数据 ['col0' => 1]
     * <p>支持参数绑定的方式 ['col0' => ['UNIX_TIMESTAMP()']]</p>
     * <p>支持单条 [...]; 或多条 [[...], [...]]</p>
     * @param string $table 表名
     * @param bool $ignore 是否使用 INSERT IGNORE 语法
     * @param string $section 数据库区域，为空时自动切换为 default
     * @return int 返回最后插入的主键ID(批量插入时返回第1个ID), 失败时返回0
     */
    public function insert(array $data, string $table, $ignore = false, string $section = '')
    {
        return (new AppPDO($this->getConnection(true, $section), $this->conf['log']))->insert($data, $table, $ignore);
    }

    /**
     * 更新记录
     * @param array $data 要更新的字段 ['col0' => 1]
     * <p>支持参数绑定的方式 ['col0' => ['n+?', 1]]</p>
     * @param array|string $where 用法参考 \Core\AppPDO::parseWhere
     * @param string $table 表名
     * @param string $section 数据库区域，为空时自动切换为 default
     * @return int 被更新的行数，否则返回0(<b>注意：不要随便用来当判断条件</b>)
     */
    public function update(array $data, $where, string $table, string $section = '')
    {
        return (new AppPDO($this->getConnection(true, $section), $this->conf['log']))->update($data, $where, $table);
    }

    /**
     * 删除记录
     * @param array|string $where 用法参考 \Core\AppPDO::parseWhere
     * @param string $table 表名
     * @param string $section 数据库区域，为空时自动切换为 default
     * @return int 被删除的行数，否则返回0(<b>注意：不要随便用来当判断条件</b>)
     */
    public function delete($where, string $table, string $section = '')
    {
        return (new AppPDO($this->getConnection(true, $section), $this->conf['log']))->delete($where, $table);
    }

}
