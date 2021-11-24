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
    public function getConfigInfo($appLabel, string $category = 'thefair'): WeChatConfig
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

    public function getAppId(): string
    {
        return $this->app_id;
    }

    public function getAppSecret(): string
    {
        return $this->secret;
    }

    public function getConfig(): Collection
    {
        return $this->config;
    }

    /**
     * $config = [
     * // 必要配置
     * 'app_id'             => 'xxxx',
     * 'mch_id'             => 'your-mch-id',
     * 'key'                => 'key-for-signature',   // API 密钥
     *
     * // 如需使用敏感接口（如退款、发送红包等）需要配置 API 证书路径(登录商户平台下载 API 证书)
     * 'cert_path'          => 'path/to/your/cert.pem', // XXX: 绝对路径！！！！
     * 'key_path'           => 'path/to/your/key',      // XXX: 绝对路径！！！！
     *
     * 'notify_url'         => '默认的订单回调地址',     // 你也可以在下单时单独设置来想覆盖它
     * ];
     *
     * @return array|mixed
     */
    public function getPay()
    {
        if ($pay = $this->config->get('pay', [])) {
            return $pay;
        }

        return $pay ?? [];
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
