<?php

namespace TheFairLib\Library\Queue;

use TheFairLib\Library\Queue\Client\RocketMQ;
use TheFairLib\Exception\ServiceException;

class ClientFactory
{

    /**
     * @param string $clientId
     *
     * @return RocketMQ|null
     */
    public function getClient(string $clientId): ?RocketMQ
    {
        $config = new Config($clientId);

        switch ($config->getDriver()) {
            case 'rocketmq':
                return make(RocketMQ::class, [$config]);
            default:
                throw new ServiceException('error config driver', [
                    'client_id' => $clientId,
                ]);
        }
    }
}
