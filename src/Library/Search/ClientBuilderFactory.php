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
     * @param string $poolName
     * @return Client
     */
    public function getClient(string $poolName)
    {
        return $this->create($poolName)->build();
    }

    /**
     * @param string $poolName
     * @return ClientBuilder
     */
    public function create(string $poolName)
    {
        /**
         * @var ConfigInterface $config
         */
        $config = ApplicationContext::getContainer()->get(ConfigInterface::class);
        $builder = ClientBuilder::create();
        if (Coroutine::getCid() > 0) {
            $handler = make(PoolHandler::class, [
                'option' => [
                    'max_connections' => (int)$config->get(sprintf('elastic.%s.pool.max_connections', $poolName), 100),
                ],
            ]);
            $builder->setHandler($handler);
        }
        return $builder;
    }
}
