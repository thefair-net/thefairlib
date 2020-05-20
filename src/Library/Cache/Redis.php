<?php
declare(strict_types=1);

namespace TheFairLib\Library\Cache;

use Hyperf\Redis\RedisFactory;
use Hyperf\Utils\ApplicationContext;

class Redis
{
    const SERVER_NAME = 'default';

    /**
     * redis 对象
     *
     * @param string $poolName
     * @return \Redis
     */
    public static function getContainer($poolName = '')
    {
        return ApplicationContext::getContainer()->get(RedisFactory::class)->get($poolName ?: self::SERVER_NAME);
    }
}
