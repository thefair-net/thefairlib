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

namespace Hyperf\Nacos\Api;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\ContainerInterface;
use Hyperf\Guzzle\CoroutineHandler;
use Hyperf\Guzzle\PoolHandler;
use Swoole\Coroutine;

abstract class AbstractNacos
{
    use AccessToken;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var ConfigInterface
     */
    protected $config;

    /**
     * @var callable
     */
    protected $handler;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->config = $container->get(ConfigInterface::class);
    }

    public function request($method, $uri, array $options = [])
    {
        $token = $this->getAccessToken();
        $token && $options[RequestOptions::QUERY]['accessToken'] = $token;
        return $this->client()->request($method, $uri, $options);
    }

    public function getServerUri(): string
    {
        $url = $this->config->get('nacos.url');

        if ($url) {
            return $url;
        }

        return sprintf(
            '%s:%d',
            $this->config->get('nacos.host', '127.0.0.1'),
            (int)$this->config->get('nacos.port', 8848)
        );
    }

    public function client(): Client
    {
        $option = $this->config->get('nacos.http_guzzle.option', []);
        return new Client([
            'base_uri' => $this->getServerUri(),
            'handler' => make(PoolHandler::class, [
                'option' => [
                    'min_connections' => $option['min_connections'] ?? 2,
                    'max_connections' => $option['max_connections'] ?? 10,
                    'connect_timeout' => $option['connect_timeout'] ?? 3.0,
                    'wait_timeout' => $option['wait_timeout'] ?? 30.0,
                    'heartbeat' => $option['heartbeat'] ?? -1,
                    'max_idle_time' => $option['max_idle_time'] ?? 60.0,
                ],
            ]),
            RequestOptions::HEADERS => [
                'charset' => 'UTF-8',
            ],
        ]);
    }
}
