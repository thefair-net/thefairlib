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

    public function setTable($table)
    {
        $this->table = $table;
        if ($this->shardingNum > 0) {
            $this->table = Context::set(__CLASS__ . ':table_name', $table);
        }
    }

    public function getTable()
    {
        $table = $this->table;
        if ($this->shardingNum > 0) {
            $table = Context::get(__CLASS__ . ':table_name');
        }
        return $table;
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
    protected function db($poolName = '')
    {
        try {
            return Db::connection(empty($poolName) ? $this->connection : $poolName);
        } catch (Throwable $e) {
            throw new ConnectionException(sprintf('error pool name: %s , msg %s', $poolName, $e->getMessage()));
        }
    }
}
