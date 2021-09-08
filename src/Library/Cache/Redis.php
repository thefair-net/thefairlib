<?php
declare(strict_types=1);

namespace TheFairLib\Library\Cache;

use Hyperf\Redis\RedisFactory;
use Hyperf\Redis\RedisProxy;
use Hyperf\Utils\ApplicationContext;

class Redis
{
    const SERVER_NAME = 'default';

    /**
     * redis 对象
     *
     * @param string $poolName
     * @return RedisProxy|\Redis
     */
    public static function getContainer(string $poolName = '')
    {
        return ApplicationContext::getContainer()->get(RedisFactory::class)->get($poolName ?: self::SERVER_NAME);
    }
}
