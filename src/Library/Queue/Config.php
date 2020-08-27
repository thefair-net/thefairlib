<?php

/**
 * File: Cinfig.php
 * File Created: Thursday, 28th May 2020 3:59:40 pm
 * Author: Yin
 */

namespace TheFairLib\Library\Queue;

use Hyperf\Contract\ConfigInterface;
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
 */
class Config
{
    private $params = [
        'host',
        'app_id',
        'app_key',
        'driver',
        'instance_id',
    ];

    /**
     * @var ConfigInterface
     */
    protected $config;

    public function __construct(ConfigInterface $config)
    {
        $this->config = $config;
    }

    /**
     * 获取config
     *
     * @param string $clientId
     *
     * @return Config
     */
    public function getConfig($clientId): Config
    {
        $config = $this->config->get('queue.' . $clientId);

        if (!$config) {
            throw new ServiceException(sprintf('%s queue config info error ', $clientId));
        }

        $this->host = isset($config['host']) ? $config['host'] : null;
        $this->app_id = isset($config['app_id']) ? $config['app_id'] : null;
        $this->app_key = isset($config['app_key']) ? $config['app_key'] : null;
        $this->driver = isset($config['driver']) ? $config['driver'] : null;
        $this->instance_id = isset($config['instance_id']) ? $config['instance_id'] : null;

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
    public function getHost()
    {
        return $this->host;
    }

    /**
     * Get the value of app_id
     */
    public function getAppId()
    {
        return $this->app_id;
    }

    /**
     * Get the value of app_key
     */
    public function getAppKey()
    {
        return $this->app_key;
    }

    /**
     * Get the value of driver
     */
    public function getDriver()
    {
        return $this->driver;
    }

    /**
     * Get the value of instance_id
     */
    public function getInstanceId()
    {
        return $this->instance_id;
    }
}
