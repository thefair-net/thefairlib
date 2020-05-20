<?php

namespace TheFairLib\Library\Lock;

use TheFairLib\Contract\LockInterface;
use TheFairLib\Library\Cache\Redis as HyperfRedis;
use Redis;

/**
 * @property int $retryDelay
 * @property int $retryCount
 * @property float $clockDriftFactor
 * @property int $quorum
 * @property array $servers
 * @property string $poolName
 * @property Redis $instance
 * Class RedisLock
 * @package TheFairLib\Library\Utils
 */
class RedisLock implements LockInterface
{
    public function __get($name)
    {
        return $this->$name;
    }

    public function __set($name, $value)
    {
        return $this->$name = $value;
    }

    public function __construct(string $poolName, int $retryDelay = 200, $retryCount = 3)
    {
        $this->poolName = $poolName;
        $this->retryDelay = $retryDelay;
        $this->retryCount = $retryCount;
        $this->clockDriftFactor = 0.01;
        $this->quorum = 1;
    }

    public function lock($resource, int $ttl = 1000)
    {
        $this->initInstances();

        $token = uniqid();
        $retry = $this->retryCount;

        do {
            $n = 0;

            $startTime = microtime(true) * 1000;

            if ($this->lockInstance($resource, $token, $ttl)) {
                $n++;
            }

            # Add 2 milliseconds to the drift to account for Redis expires
            # precision, which is 1 millisecond, plus 1 millisecond min drift
            # for small TTLs.
            $drift = ($ttl * $this->clockDriftFactor) + 2;

            $validityTime = $ttl - (microtime(true) * 1000 - $startTime) - $drift;

            if ($n >= $this->quorum && $validityTime > 0) {
                return [
                    'validity' => $validityTime,
                    'resource' => $resource,
                    'token' => $token,
                ];
            } else {
                $this->unlockInstance($resource, $token);
            }

            // Wait a random delay before to retry
            $delay = mt_rand(floor($this->retryDelay / 2), $this->retryDelay);
            usleep($delay * 1000);

            $retry--;
        } while ($retry > 0);

        return false;
    }

    public function unlock(array $lock)
    {
        $this->initInstances();
        $resource = $lock['resource'];
        $token = $lock['token'];

        return $this->unlockInstance($resource, $token);
    }

    private function initInstances()
    {
        if (empty($this->instance)) {
            $this->instance = HyperfRedis::getContainer($this->poolName);
        }
    }

    /**
     * 将设置的键，如果它不存在，与1000 毫秒 的ttl
     *
     * @param $resource
     * @param $token
     * @param $ttl
     * @return bool
     */
    private function lockInstance($resource, $token, $ttl)
    {
        //这个参数我们填的是NX，意思是SET IF NOT EXIST，即当key不存在时，我们进行set操作；若key已经存在，则不做任何操作；
        //PX 为毫秒
        return $this->instance->set($resource, $token, ['NX', 'PX' => $ttl]);
    }

    /**
     * 删除锁
     *
     * @param $resource
     * @param $token
     * @return int
     */
    private function unlockInstance($resource, $token)
    {
//        请不要分开执行,判断加锁与解锁是不是同一个客户端
//        if ($this->instance->get($resource) == $token) {
//        可能会发生阻塞，若在此时，这把锁突然不是这个客户端的，则会误解锁
//            return $this->instance->del($resource);
//        }
        $script = '
            if redis.call("GET", KEYS[1]) == ARGV[1] then
                return redis.call("DEL", KEYS[1])
            else
                return 0
            end
        ';
        return $this->instance->eval($script, [$resource, $token], 1);
    }
}
