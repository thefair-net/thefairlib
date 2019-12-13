<?php

/**
 * File: Redis.php
 * File Created: Wednesday, 11th December 2019 2:48:32 pm
 * Author: Yin
 */

namespace TheFairLib\DB\Redis;

class RedisCluster extends \RedisCluster
{
    public function __construct($parameters)
    {
        // RedisCluster 只需要 "host:port"
        foreach ($parameters as &$url) {
            $config = parse_url($url);
            $url = $config['host'] . ':' . $config['port'];
        }

        parent::__construct(NULL, $parameters);
    }

    /**
     * 兼容predis zRange($key, $start, $end, 'withscores')
     *
     * Returns a range of elements from the ordered set stored at the specified key,
     * with values in the range [start, end]. start and stop are interpreted as zero-based indices:
     * 0 the first element,
     * 1 the second ...
     * -1 the last element,
     * -2 the penultimate ...
     *
     * @param   string  $key
     * @param   int     $start
     * @param   int     $end
     * @param   bool    $withscores
     * @return  array   Array containing the values in specified range.
     * @link    http://redis.io/commands/zrange
     * @example
     * <pre>
     * $redis->zAdd('key1', 0, 'val0');
     * $redis->zAdd('key1', 2, 'val2');
     * $redis->zAdd('key1', 10, 'val10');
     * $redis->zRange('key1', 0, -1); // array('val0', 'val2', 'val10')
     * // with scores
     * $redis->zRange('key1', 0, -1, true); // array('val0' => 0, 'val2' => 2, 'val10' => 10)
     * </pre>
     */
    public function zRange($key, $start, $end, $withscores = null)
    {
        if ($withscores) {
            $data = parent::zRange($key, $start, $end, true);
        } else {
            $data = parent::zRange($key, $start, $end);
        }

        return $data;
    }

    public function disconnect()
    {
        $this->close();
    }
}
