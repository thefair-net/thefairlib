<?php
declare(strict_types=1);

namespace TheFairLib\Library\Search;

use Elasticsearch\Client;
use Hyperf\Utils\ApplicationContext;

class Elastic
{
    const SERVER_NAME = 'default';

    /**
     * redis 对象
     *
     * @param string $poolName
     * @return Client
     */
    public static function getContainer(string $poolName = '')
    {
        return ApplicationContext::getContainer()->get(ClientBuilderFactory::class)->getClient($poolName ?: self::SERVER_NAME);
    }
}
