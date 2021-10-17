<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace Hyperf\Nacos;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Nacos\Api\NacosConfig;
use Hyperf\Nacos\Model\ConfigModel;
use Psr\Container\ContainerInterface;

class Client
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var ConfigInterface
     */
    protected $config;

    /**
     * @var NacosConfig
     */
    protected $client;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->config = $container->get(ConfigInterface::class);
        $this->client = $container->get(NacosConfig::class);
    }

    public function pull(): array
    {
        $listener = $this->config->get('nacos.listener_config', []);

        $config = [];
        foreach ($listener as $item) {
            $model = new ConfigModel($item);
            if (!empty($item['data_id'])
                && strpos($item['data_id'], '.') === false // . 带 . 的 data id 不生效
                && $content = $this->client->get($model)
            ) {
                $config[$item['data_id']] = $model->parse($content);
            }
        }
        return $config;
    }
}
