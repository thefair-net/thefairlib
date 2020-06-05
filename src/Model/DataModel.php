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

    protected $suffix = null;

    /**
     * 是否自动维护时间戳
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The name of the "created at" column.
     *
     * @var string
     */
    const CREATED_AT = 'ctime';

    /**
     * The name of the "updated at" column.
     *
     * @var string
     */
    const UPDATED_AT = 'utime';

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
            Context::set($this->getConttextName('table_name'), $table);
        }
    }

    private function getConttextName($name)
    {
        return get_class($this) . ':' . $name;
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
            $table = Context::get($this->getConttextName('table_name'));
        }
        return $table;
    }

    protected function originalTableName($tableName)
    {
        $name = $this->getConttextName('original_table_name');
        if (Context::has($name)) {
            $tableName = Context::get($name);
        } else {
            Context::set($name, $tableName);
        }
        return $tableName;
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
            Context::set($this->getConttextName('primary_key'), $primaryKey);
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
            $primaryKey = Context::get($this->getConttextName('primary_key'));
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
        return intval(crc32(md5((string)$shardingKey)));
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
        $tableName = $this->originalTableName($tableName ?: $this->table);
        if (empty($tableName) || ($shardingKey !== null && empty($this->shardingNum))) {
            throw new EmptyException('M Conf Err');
        }
        return $tableName . ($shardingKey !== null ? '_' . $this->getShardingTableNum($shardingKey) : '');
    }

    /**
     * 获得分表表名
     *
     * @param $shardingKey
     * @return Builder
     */
    public function setShardingId($shardingKey)
    {
        if ($this->isShardingNum()) {
            $this->setSuffix($this->getShardingTableNum($shardingKey));
            $this->table = $this->getTableName($shardingKey);
            $this->setTable($this->table);
            $this->setKeyName($this->primaryKey);
        }
        return $this->newQuery();
    }

    protected function getSuffix()
    {
        $suffix = $this->suffix;
        if ($this->isShardingNum()) {
            $suffix = Context::get($this->getConttextName('suffix'));
        }
        return $suffix;
    }

    protected function setSuffix($suffix)
    {
        $this->suffix = $suffix;
        if ($this->isShardingNum()) {
            Context::set($this->getConttextName('suffix'), $suffix);
        }
    }

    /**
     * 获得分表表名
     *
     * @param $shardingKey
     * @return Builder
     */
    public static function shardingId($shardingKey)
    {
        $instance = new static;
        $instance->setShardingId($shardingKey);
        return $instance->newQuery();
    }

    /**
     *      * $attributes
     *
     * @param array $attributes
     * @param bool $exists
     * @return Model
     */
    public function newInstance($attributes = [], $exists = false)
    {
        $model = parent::newInstance($attributes, $exists);
        $model->setShardingId($this->getSuffix());

        return $model;
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
        if (!preg_match('/^\d+$/', (string)$shardingKey)) {
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

    /**
     * Db 连接
     *
     * @param string $poolName
     * @return ConnectionInterface
     * @throws ConnectionException
     */
    public static function getDb($poolName = '')
    {
        try {
            return (new static())->db($poolName);
        } catch (Throwable $e) {
            throw new ConnectionException(sprintf('error pool name: %s , msg %s', $poolName, $e->getMessage()));
        }
    }

//    public function __call($method, $parameters)
//    {
//        rd_debug([$method, $parameters]);
//        try {
//            $this->rewriteTableName($method, $parameters);
//            return parent::__call($method, $parameters);
//        } catch (\Throwable $e) {
//            throw $e;
//        }
//    }
//
//    public static function __callStatic($method, $parameters)
//    {
//        $class = new static();
//        if ($class->isShardingNum()) {
//            /**
//             * 通过主键重写 sharding key
//             *
//             * @var PrimaryKeyBuilder $primaryKeyBuilder
//             */
//            $primaryKeyBuilder = make(PrimaryKeyBuilder::class, [$method, $parameters, $class->primaryKey]);
//            if (!$primaryKeyBuilder->isMethod()) {//判断是否重写了对应的方法
//                throw new ServiceException(sprintf('目前不支持 %s 方法', $method));
//            }
//        }
//        return parent::__callStatic($method, $parameters); // TODO: Change the autogenerated stub
//    }
//
//    public static function destroy($id)
//    {
//        (new static())->rewriteTableName('destroy', [$id]);
//        return parent::destroy($id);
//    }

//    /**
//     * 分表时，保存对象必须使用主键
//     *
//     * @param array $options
//     * @return bool
//     */
//    public function save(array $options = []): bool
//    {
//        if ($this->isShardingNum()) {
//            $id = $this->getAttributes()[$this->primaryKey] ?? null;
//            if (empty($id)) {//主键不能为空，为 0 也不行
//                throw new ServiceException('sharding key error', ['class_nane' => get_class($this) . '::save']);
//            }
//            $this->table = $this->getTableName($id);
//            $this->setTable($this->table);
//            $this->setKeyName($this->primaryKey);
//        }
//        return parent::save($options);
//    }
//
//    private function rewriteTableName($method, $parameters)
//    {
//        rd_debug([get_class($this), $this->{$this->primaryKey}, __FUNCTION__, $method, $parameters, __LINE__]);
//        if ($this->isShardingNum()) {
//            /**
//             * 通过主键重写 sharding key
//             *
//             * @var PrimaryKeyBuilder $primaryKeyBuilder
//             */
//            $primaryKeyBuilder = make(PrimaryKeyBuilder::class, [$method, $parameters, $this->primaryKey]);
//            if ($primaryKeyBuilder->isMethod()) {//判断是否重写了对应的方法
//                $id = $primaryKeyBuilder->getId();
//                if (empty($id)) {//主键不能为空，为 0 也不行
//                    throw new ServiceException('sharding key error', ['class_nane' => get_class($this) . '::' . $method]);
//                }
//                $this->setTable($this->getTableName($id));
//                $this->setKeyName($this->primaryKey);
//            }
//        }
//    }
}
