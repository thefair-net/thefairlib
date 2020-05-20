<?php

namespace TheFairLib\Library\Lock;

use TheFairLib\Contract\LockInterface;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Utils\Context;
use Psr\Container\ContainerInterface;
use Redis;

/**
 * Class RedisLockFactory
 * @package TheFairLib\Library\Utils
 */
class RedisLockFactory
{
    public function __invoke(ContainerInterface $container)
    {
        $config = $container->get(ConfigInterface::class);
        $lockConfig = $config->get('lock', false);

        if (!$lockConfig['enable']) {
            return null;
        }
        $options = $lockConfig['options'][$lockConfig['drive']];
        return make(RedisLock::class, [$options['pool_name'], $options['retry_delay'], $options['retry_count']]);
    }
}
