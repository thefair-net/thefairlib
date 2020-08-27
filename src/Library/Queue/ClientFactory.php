<?php

namespace TheFairLib\Library\Queue;

use TheFairLib\Library\Queue\Client\RocketMQ;
use Hyperf\Di\Annotation\Inject;
use TheFairLib\Exception\ServiceException;

class ClientFactory
{
    /**
     * @Inject
     * @var Config
     */
    private $config;

    /**
     * @param string $clientId
     *
     * @return RocketMQ
     */
    public function getClient($clientId)
    {
        $config = $this->config->getConfig($clientId);

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
