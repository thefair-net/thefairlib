<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://doc.hyperf.io
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace TheFairLib\Library\Search;

use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Guzzle\RingPHP\PoolHandler;
use Hyperf\Utils\ApplicationContext;
use Swoole\Coroutine;

class ClientBuilderFactory
{
    /**
     * @var array
     */
    private static $config;

    /**
     * @param string $poolName
     * @return Client
     */
    public function getClient(string $poolName)
    {
        $builder = $this->create($poolName);
        $options = arrayGet(self::$config, sprintf('%s', $poolName));
        return $builder->setHosts([
            [
                'host' => arrayGet($options, 'host', '127.0.0.1'),
                'port' => arrayGet($options, 'port', 9200),
                'user' => arrayGet($options, 'user', ''),
                'pass' => arrayGet($options, 'pass', ''),
                'scheme' => arrayGet($options, 'scheme', 'http'),
            ],
        ])->build();
    }

    /**
     * @param string $poolName
     * @return ClientBuilder
     */
    public function create(string $poolName)
    {
        self::$config = ApplicationContext::getContainer()->get(ConfigInterface::class)->get('elastic', []);
        $builder = ClientBuilder::create();
        if (Coroutine::getCid() > 0) {
            $handler = make(PoolHandler::class, [
                'option' => [
                    'max_connections' => (int)arrayGet(self::$config, sprintf('%s.pool.max_connections', $poolName), 100),
                ],
            ]);
            $builder->setHandler($handler);
        }
        return $builder;
    }
}
