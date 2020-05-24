<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://doc.hyperf.io
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf-cloud/hyperf/blob/master/LICENSE
 */

namespace TheFairLib\Model;

use Hyperf\Database\Exception\QueryException;
use Hyperf\Database\Model\Builder;
use TheFairLib\Contract\LockInterface;
use TheFairLib\Exception\EmptyException;
use TheFairLib\Exception\ServiceException;
use Hyperf\Database\ConnectionInterface;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Pool\Exception\ConnectionException;
use Hyperf\Utils\Context;
use Psr\EventDispatcher\EventDispatcherInterface;
use Redis;
use TheFairLib\Model\Builder\PrimaryKeyBuilder;
use Throwable;

/**
 * Class DataModel
 * @package TheFairLib\Model
 *
 * @method static Builder create(array $attributes)
 * @method static Builder where($column, $operator = null, $value = null, $boolean = 'and')
 * @method static Builder firstOrCreate(array $attributes, array $values = [])
 * @method static Builder updateOrCreate(array $attributes, array $values = [])
 * @method static Builder find($id, $columns = ['*'])
 */
abstract class DataModel extends Model
{
    const SERVER_NAME = 'default';

    protected $connection = 'default';

    protected $shardingNum = 0;

    protected $shardingKey = '';

    /**
     * 锁，目前是使用 redis 现实.
     *
     * @Inject
     * @var LockInterface
     */
    protected $lock;

    /**
     * 是否自动维护时间戳
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * 触发事件.
     *
     * @Inject
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * 底层方法重写
     *
     * @param string $table
     * @return void
     */
    public function setTable($table)
    {
        $this->table = $table;
        if ($this->isShardingNum()) {
            $this->table = Context::set(__CLASS__ . ':table_name', $table);
        }
    }

    /**
     * 底层方法重写
     *
     * @return string
     */
    public function getTable()
    {
        $table = $this->table;
        if ($this->isShardingNum()) {
            $table = Context::get(__CLASS__ . ':table_name');
        }
        return $table;
    }

    /**
     * 底层方法重写
     *
     * @param string $primaryKey
     * @return $this
     */
    public function setKeyName($primaryKey): self
    {
        $this->primaryKey = $primaryKey;
        if ($this->isShardingNum()) {
            $this->primaryKey = Context::set(__CLASS__ . ':table_name:primary_key', $primaryKey);
        }
        return $this;
    }

    /**
     * 底层方法重写
     *
     * @return string
     */
    public function getKeyName()
    {
        $primaryKey = $this->primaryKey;
        if ($this->isShardingNum()) {
            $primaryKey = Context::get(__CLASS__ . ':table_name:primary_key');
        }
        return $primaryKey;
    }

    protected function isShardingNum(): bool
    {
        return $this->shardingNum > 0;
    }


    /**
     * sharding
     *
     * @param string|int
     * @return int
     */
    protected function getShardingId($shardingKey): int
    {
        return intval(crc32(md5($shardingKey)));
    }

    /**
     * 获取数据库uuid.
     *
     * @return int uuid
     */
    protected function getUuid()
    {
        return getUuid();
    }

    /**
     * 获得分表表名
     *
     * @param null $shardingKey
     * @param string $tableName
     * @return string
     */
    protected function getTableName($shardingKey = null, $tableName = '')
    {
        $tableName = !empty($tableName) ?: $this->table;
        if (empty($tableName) || ($shardingKey !== null && empty($this->shardingNum))) {
            throw new EmptyException('M Conf Err');
        }
        return $tableName . ($shardingKey !== null ? '_' . $this->getShardingTableNum($shardingKey) : '');
    }

    /**
     * 存储.
     *
     * @param string $serverName 集群标识
     * @return Redis
     */
    protected function Storage($serverName = self::SERVER_NAME)
    {
        return \TheFairLib\Library\Cache\Redis::getContainer($serverName);
    }

    /**
     * 缓存.
     *
     * @param string $serverName
     * @return Redis
     */
    protected function Cache($serverName = self::SERVER_NAME)
    {
        return \TheFairLib\Library\Cache\Redis::getContainer($serverName);
    }

    /**
     * @param $type
     * @param $dataType
     * @return string
     */
    protected function getPrefix($type, $dataType)
    {
        $productPrefix = '';
        if (env('PRODUCT_NAME')) {
            $productPrefix = env('PRODUCT_NAME') . '#';
        }
        if (!in_array($type, ['Cache', 'Storage']) || !in_array($dataType, ['key', 'hash', 'set', 'zset', 'list', 'string', 'geo'])) {
            throw new ServiceException('Redis cache prefix config error!');
        }
        return $productPrefix . $type . '#' . env('PHASE', 'prod') . '#' . $dataType . '#';
    }

    /**
     * 分表取模
     *
     * @param $shardingKey
     * @return int
     */
    private function getShardingTableNum($shardingKey): int
    {
        if (!is_int($shardingKey)) {
            $shardingKey = $this->getShardingId($shardingKey);
        }
        return (int)$shardingKey % $this->shardingNum;
    }

    /**
     * Db 连接
     *
     * @param string $poolName
     * @return ConnectionInterface
     * @throws ConnectionException
     */
    public function db($poolName = '')
    {
        try {
            return Db::connection(empty($poolName) ? $this->connection : $poolName);
        } catch (Throwable $e) {
            throw new ConnectionException(sprintf('error pool name: %s , msg %s', $poolName, $e->getMessage()));
        }
    }

    public function __call($method, $parameters)
    {
        rd_debug([$method, $parameters]);
        try {
            if ($this->isShardingNum()) {
                /**
                 * 通过主键重写 sharding key
                 *
                 * @var PrimaryKeyBuilder $primaryKeyBuilder
                 */
                $primaryKeyBuilder = make(PrimaryKeyBuilder::class, [$method, $parameters, $this->primaryKey]);
                if ($primaryKeyBuilder->isMethod()) {//判断是否重写了对应的方法
                    $id = $primaryKeyBuilder->getId();
                    if (empty($id)) {//主键不能为空，为 0 也不行
                        throw new QueryException('sharding key error');
                    }
                    $this->table = $this->getTableName($id);
                    $this->setTable($this->table);
                    $this->setKeyName($this->primaryKey);
                }
            }
            return parent::__call($method, $parameters);
        } catch (\Throwable $e) {
            throw $e;
        }
    }

    public static function __callStatic($method, $parameters)
    {
        if ($class = (new static()) && $class->isShardingNum()) {
            /**
             * 通过主键重写 sharding key
             *
             * @var PrimaryKeyBuilder $primaryKeyBuilder
             */
            $primaryKeyBuilder = make(PrimaryKeyBuilder::class, [$method, $parameters, $class->primaryKey]);
            if (!$primaryKeyBuilder->isMethod()) {//判断是否重写了对应的方法
                throw new ServiceException(sprintf('目前不支持 %s 方法', $method));
            }
        }
        return parent::__callStatic($method, $parameters); // TODO: Change the autogenerated stub
    }

    public static function destroy($id)
    {
        if ($class = (new static()) && $class->isShardingNum()) {
            if (is_array($id)) {
                throw new ServiceException('目前不支持批量删除');
            }
            $class->table = $class->getTableName($id);
            $class->setTable($class->table);
            $class->setKeyName($class->primaryKey);
        }
        return parent::destroy($id);
    }
}
