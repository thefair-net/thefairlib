<?php
/***************************************************************************
 *
 * Copyright (c) 2021 liumingzhi, Inc. All Rights Reserved
 *
 **************************************************************************
 *
 * @file WeChatProject.php
 * @author liumingzhi(liumingzhij26@gmail.com)
 * @date 2021-02-23 10:53:00
 *
 **/

namespace TheFairLib\Library\WeChat\EasyWeChat\Core;

use Hyperf\Utils\Collection;
use Hyperf\Utils\Context;
use TheFairLib\Exception\ServiceException;

/**
 * @property string $app_id
 * @property Collection $config
 * @property string $secret
 */
class WeChatConfig
{
    private $params = [
        'app_id',
        'secret',
        'config',
    ];

    /**
     * @var Collection
     */
    protected $config;

    /**
     * 项目信息
     *
     * @param $appLabel
     * @param string $category
     * @return WeChatConfig
     */
    public function getConfigInfo($appLabel, $category = 'thefair'): WeChatConfig
    {
        $config = config(sprintf('api.wechat.%s.%s', $category, $appLabel));
        if (!$config) {
            throw new ServiceException(sprintf('%s config info error ', $appLabel));
        }

        $this->init($config);
        return $this;
    }

    /**
     * 初始化项目信息
     *
     * @param $config
     */
    private function init($config)
    {
        $this->config = collect($config);
        $this->app_id = $config['app_id'] ?? '';
        $this->secret = $config['secret'] ?? '';
    }

    public function getAppId()
    {
        return $this->app_id;
    }

    public function getAppSecret()
    {
        return $this->secret;
    }

    public function getConfig(): Collection
    {
        return $this->config;
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
}
