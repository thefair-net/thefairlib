<?php

/**
 * File: Config.php
 * File Created: Thursday, 28th May 2020 3:59:40 pm
 * Author: Yin
 */

namespace TheFairLib\Library\Queue;

use Hyperf\Utils\Collection;
use Hyperf\Utils\Context;
use TheFairLib\Exception\ServiceException;

/**
 * Config
 *
 * @property string $host
 * @property string $app_id
 * @property string $app_key
 * @property string $driver
 * @property string $instance_id
 * @property array $topic
 * @property array $group_id
 * @property Collection $config
 */
class Config
{
    private $params = [
        'host',
        'app_id',
        'app_key',
        'driver',
        'instance_id',
        'topic',
        'group_id',
        'config',
    ];

    /**
     * @var Collection
     */
    public $config;

    public function __construct(string $clientId)
    {
        $config = config("queue.$clientId");
        if (!$config) {
            throw new ServiceException(sprintf('%s config info error ', $clientId));
        }
        $this->init($config);
    }

    /**
     * 初始化项目信息
     *
     * @param $config
     */
    private function init($config)
    {
        $this->config = collect($config);
        $this->host = $config['host'] ?? '';
        $this->app_id = $config['app_id'] ?? '';
        $this->app_key = $config['app_key'] ?? '';
        $this->driver = $config['driver'] ?? '';
        $this->instance_id = $config['instance_id'] ?? '';
        $this->topic = $config['topic'] ?? [];
        $this->group_id = $config['group_id'] ?? [];
    }

    /**
     * 获取config
     *
     * @param string $clientId
     *
     * @return Config
     */
    public function getConfig(string $clientId): Config
    {
        $config = $this->config->get('queue.' . $clientId);

        if (!$config) {
            throw new ServiceException(sprintf('%s queue config info error ', $clientId));
        }
        $this->config = collect($config);

        $this->host = $config['host'] ?? '';
        $this->app_id = $config['app_id'] ?? '';
        $this->app_key = $config['app_key'] ?? '';
        $this->driver = $config['driver'] ?? '';
        $this->instance_id = $config['instance_id'] ?? '';
        $this->topic = $config['topic'] ?? [];
        $this->group_id = $config['group_id'] ?? [];

        return $this;
    }

    public function __get($name)
    {
        if (!in_array($name, $this->params)) {
            throw new ServiceException('error param', ['name' => $name]);
        }
        return Context::get(__CLASS__ . ':' . $name);
    }

    public function __set($name, $value)
    {
        if (!in_array($name, $this->params)) {
            throw new ServiceException('error param', [$name => $value]);
        }
        return Context::set(__CLASS__ . ':' . $name, $value);
    }

    /**
     * Get the value of host
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * Get the value of app_id
     */
    public function getAppId(): string
    {
        return $this->app_id;
    }

    /**
     * Get the value of app_key
     */
    public function getAppKey(): string
    {
        return $this->app_key;
    }

    /**
     * Get the value of driver
     */
    public function getDriver(): string
    {
        return $this->driver;
    }

    /**
     * Get the value of instance_id
     */
    public function getInstanceId(): string
    {
        return $this->instance_id;
    }

    public function getTopic(string $topicName)
    {
        return $this->topic[$topicName] ?? '';
    }

    public function getGroupId(string $groupId)
    {
        return $this->group_id[$groupId] ?? '';
    }
}
