<?php

namespace JDI\Tests\Services;

use JDI\Services\PDOSharding;
use PHPUnit\Framework\TestCase;

class DBTest extends TestCase
{
    public static function setUpBeforeClass()
    {
        $sql = "DROP TABLE IF EXISTS `test`;
                CREATE TABLE `test` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `name` varchar(50) NOT NULL DEFAULT '',
                  `order` int(11) NOT NULL DEFAULT '0',
                  PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        db()->query($sql);
    }

    /**
     * 插入
     * @return array
     */
    public function testInsert()
    {
        $ds['insert_sql'] = db()->query("insert into test(name, `order`) values(?, ?)", ['sparrow_1', 1]);
        // insert KV, insert ignore
        $ds['insert_kv'] = db()->insert(['name' => 'sparrow_2', 'order' => 2], 'test', true);
        // insert ignore
        $ds['insert_kv2'] = db()->insert(['name' => ['?', 'sparrow_3'], 'order' => ['UNIX_TIMESTAMP()']], 'test', true);
        // 批量插入
        $ds['insert_kv_many'] = db()->insert([['name' => 'sparrow_m1', 'order' => 1], ['name' => 'sparrow_m2', 'order' => 2]], 'test');

        $this->assertIsNumeric($ds['insert_sql']);
        $this->assertIsNumeric($ds['insert_kv']);
        $this->assertIsNumeric($ds['insert_kv2']);
        $this->assertIsNumeric($ds['insert_kv_many']);

        return $ds;
    }

    /**
     * 查询
     * @depends testInsert
     * @param $ds
     * @return array
     */
    public function testSelect(array $ds)
    {
        $ds['select_sql'] = db()->getOne('select * from test where id=:id', ['id' => $ds['insert_sql']]);
        $ds['select_sql2'] = db()->getAll('select * from test where id in(?,?)', [$ds['insert_kv'], $ds['insert_kv2']]);
        // WHERE 参数绑定，['col0=?', 1]
        $ds['select_one'] = db()->selectOne('*', ['id=?', $ds['insert_sql']], 'test');
        // WHERE 参数绑定，['col0=?', [1]]; 结果同上
        $ds['select_one2'] = db()->selectOne('*', ['id=?', [$ds['insert_sql']]], 'test');
        // WHERE and KV
        $ds['select_one3'] = db()->selectOne('*', ['name' => 'sparrow_2', 'order' => 2], 'test');
        // 固定字符串条件
        $ds['select_one4'] = db()->selectOne('*', '`order`=2', 'test');
        // 无条件
        $ds['select_all3'] = db()->selectAll('*', '', 'test', 'id desc', [5]);

        // 查询业务示例
        $name = 'sparrow';
        $order = 2;
        $where = [];

        if ($name) {
            $where[] = ['and name like ?', "%{$name}%"]; // 或者带上key $where['name'] 也可以，可用于覆盖同 key 的条件
        }

        if ($order) {
            $where['order'] = $order; // 或者 $where[] = ['and `order`=?', $order];
        }
        // 直接使用 WHERE 组合条件
        $ds['select_where'] = db()->selectAll('*', $where, 'test');

        // 纯 SQL 时，需先 parseWhere() 转换成 placeholder, binds 形式
        $hBinds = db()->parseWhere($where);
        $ds['select_where2'] = db()->getAll("select * from test {$hBinds[0]}", $hBinds[1]);

        $this->assertEquals('sparrow_1', $ds['select_sql']['name']);
        $this->assertEquals(['sparrow_2', 'sparrow_3'], array_column($ds['select_sql2'], 'name'));
        $this->assertEquals('sparrow_1', $ds['select_one']['name']);
        $this->assertEquals('sparrow_1', $ds['select_one2']['name']);
        $this->assertEquals('sparrow_2', $ds['select_one3']['name']);
        $this->assertEquals(2, $ds['select_one4']['order']);
        $this->assertEquals(['sparrow_m2', 'sparrow_m1', 'sparrow_3', 'sparrow_2', 'sparrow_1'], array_column($ds['select_all3'], 'name'));
        $this->assertEquals(['sparrow_2', 'sparrow_m2'], array_column($ds['select_where'], 'name'));
        $this->assertEquals(['sparrow_2', 'sparrow_m2'], array_column($ds['select_where2'], 'name'));

        return $ds;
    }

    /**
     * 更新
     * @depends testSelect
     * @param array $ds
     * @return array
     */
    public function testUpdate(array $ds)
    {
        $ds['update_sql'] = db()->query('update test set `order`=`order`+1 where id=:id', ['id' => $ds['insert_sql']]);
        // WHERE 参数绑定
        $ds['update_kv'] = db()->update(['name' => 'sparrow_u1'], ['id=?', $ds['insert_sql']], 'test');
        // SET 参数绑定(其 value 写在数组里面，若用到数据库的原生函数亦复如是)；WHERE KV
        $ds['update_kv2'] = db()->update(['order' => ['`order`+?', 1], 'name' => ['?', 'update_kv']], ['id' => $ds['insert_sql']], 'test');
        $ds['update_kv3'] = db()->update(['order' => ['UNIX_TIMESTAMP()']], ['id' => $ds['insert_kv']], 'test');
        $ds['select_all4'] = db()->selectAll('*', ['id in(?,?)', $ds['insert_sql'], $ds['insert_kv']], 'test');

        $this->assertEquals(1, $ds['update_sql']);
        $this->assertEquals(1, $ds['update_kv']);
        $this->assertEquals(1, $ds['update_kv2']);
        $this->assertEquals(1, $ds['update_kv3']);
        $this->assertEquals(['update_kv', 'sparrow_2'], array_column($ds['select_all4'], 'name'));

        return $ds;
    }

    /**
     * 删除
     * @depends testUpdate
     * @param array $ds
     * @return array
     */
    public function testDelete(array $ds)
    {
        $ds['delete_sql'] = db()->query('delete from test where id=?', [$ds['insert_sql']]);
        $ds['delete_kv'] = db()->delete(['id' => $ds['insert_kv']], 'test');
        $ids = implode(',', array_column($ds['select_all3'], 'id'));
        // 不参数绑定
        $ds['delete_all'] = db()->delete(["id in({$ids})"], 'test');
        $ds['select_all5'] = db()->selectAll('*', ["id in({$ids})"], 'test');

        $this->assertEquals(1, $ds['delete_sql']);
        $this->assertEquals(1, $ds['delete_kv']);
        $this->assertGreaterThanOrEqual(1, $ds['delete_all']);
        $this->assertEquals([], $ds['select_all5']);

        return $ds;
    }

    /**
     * 事务
     * @depends testDelete
     */
    public function testTransaction()
    {
        $transaction = db()->beginTransaction();
        $ds2['trans_insert'] = $transaction->insert(['name' => 'trans', 'order' => 99], 'test');
        $ds2['trans_select'] = $transaction->selectOne('*', ['id=?', $ds2['trans_insert']], 'test');
        $ds2['trans_commit'] = $transaction->commit();
        $ds2['trans_delete'] = $transaction->delete(['id=?', $ds2['trans_insert']], 'test');
        $ds2['trans_select2'] = $transaction->selectOne('*', ['id=?', $ds2['trans_insert']], 'test');

        $this->assertIsNumeric($ds2['trans_insert']);
        $this->assertEquals('trans', $ds2['trans_select']['name']);
        $this->assertEquals(true, $ds2['trans_commit']);
        $this->assertEquals(1, $ds2['trans_delete']);
        $this->assertEquals(false, $ds2['trans_select2']);
    }

    /**
     * 分表
     * 自动切换 table, section; 不需显式指定
     */
    public function testShard()
    {
        $sharding = db()->shard('test', 123);
        $ds3['sharding_insert'] = $sharding->insert([['name' => 'Hello', 'order' => ['UNIX_TIMESTAMP()']], ['name' => 'Sparrow', 'order' => 10]]);
        // 使用 $sharding->selectAll(), 不用指定分表 table, section; 其它非纯 SQL 方法同理
        $ds3['sharding_select'] = $sharding->selectAll('*', '', '', 'id DESC', [2]);
        $ids = array_column($ds3['sharding_select'], 'id');
        $ds3['sharding_update'] = $sharding->update(['order' => 1], ['id IN(?,?)', $ids]);
        // 使用 $sharding->getAll(), 只需指定分表 table
        $ds3['sharding_select2'] = $sharding->getAll("select * from {$sharding->table} where id IN(?,?)", $ids);
        $ds3['sharding_delete'] = $sharding->delete(['id IN(?,?)', $ids]);
        // 也可以使用 db()->getAll(), 但需要指定分表 table, section
        $ds3['sharding_select3'] = db()->getAll("select * from {$sharding->table} where id IN(?,?)", $ids, false, $sharding->section);

        $this->assertIsNumeric($ds3['sharding_insert']);
        $this->assertEquals(['Sparrow', 'Hello'], array_column($ds3['sharding_select'], 'name'));
        $this->assertEquals(2, $ds3['sharding_update']);
        $this->assertEquals(['Hello', 'Sparrow'], array_column($ds3['sharding_select2'], 'name'));
        $this->assertEquals(2, $ds3['sharding_delete']);
        $this->assertEquals([], $ds3['sharding_select3']);

        return $sharding;
    }

    /**
     * 分表事务
     * 自动切换 section, table; 不需显式指定
     * @depends testShard
     * @param PDOSharding $sharding
     */
    public function testShardTransaction(PDOSharding $sharding)
    {
        $shardingTransaction = $sharding->beginTransaction();
        // 分表事务对象，非纯 SQL 方法同理可以不指定 table, section
        $ds4['sharding_trans_insert'] = $shardingTransaction->insert(['name' => 'sharding_trans']);
        $ds4['sharding_trans_select'] = $shardingTransaction->selectOne('*', '', '', 'id DESC');
        $ds4['sharding_trans_update'] = $shardingTransaction->update(['order' => ['`order`+1']], ['id' => $ds4['sharding_trans_select']['id']]);
        $ds4['sharding_trans_select2'] = $shardingTransaction->selectOne('*', ['id' => $ds4['sharding_trans_select']['id']]);
        $ds4['sharding_trans_rollback'] = $shardingTransaction->rollBack();
        $ds4['sharding_trans_select3'] = $shardingTransaction->selectOne('*', ['id' => $ds4['sharding_trans_select']['id']]);

        $this->assertEquals('sharding_trans', $ds4['sharding_trans_select']['name']);
        $this->assertEquals(1, $ds4['sharding_trans_select2']['order']);
        $this->assertEquals(false, $ds4['sharding_trans_select3']);
    }
}