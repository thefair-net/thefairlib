<?php
/***************************************************************************
 *
 * Copyright (c) 2021 liumingzhi, Inc. All Rights Reserved
 *
 **************************************************************************
 *
 * @file StsConfig.php
 * @author liumingzhi(liumingzhij26@gmail.com)
 * @date 2021-09-15 19:15:00
 *
 **/

namespace TheFairLib\Library\File\AliYun;


use TheFairLib\Exception\ServiceException;

class StsConfig
{

    protected $url = 'https://sts.aliyuncs.com';

    protected $accessKeySecret;

    protected $accessKeyId;

    /**
     * 指定角色的 ARN ，角色策略权限
     *
     * @var string
     */
    protected $roleArn = '';

    /**
     * 用户自定义参数。此参数用来区分不同的 token，可用于用户级别的访问审计。格式：^[a-zA-Z0-9\.@\-_]+$
     *
     * @var string
     */
    protected $roleSessionName = 'client1';

    /**
     * 指定的过期时间
     *
     * @var string
     */
    protected $durationSeconds = '3600';

    /**
     * 方便调用时获取不同的权限
     *
     * @var string
     */
    protected $type = '';

    /**
     * @var string
     */
    protected $bucket;

    /**
     * @var array
     */
    protected $config = [];

    protected $ossOrigin = '';

    /**
     * @return string
     */
    public function getOssOrigin(): string
    {
        return $this->ossOrigin;
    }

    /**
     * @param string $ossOrigin
     */
    public function setOssOrigin(string $ossOrigin): void
    {
        $this->ossOrigin = $ossOrigin;
    }

    /**
     * 项目信息
     *
     * @param string $bucket
     * @return StsConfig
     */
    public function getConfigInfo(string $bucket): StsConfig
    {
        $config = config(sprintf('api.ali.sts.%s', $bucket));
        if (!$config) {
            throw new ServiceException(sprintf('%s config info error ', $bucket));
        }
        $this->setBucket($bucket);
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
        $this->setAccessKeyId(arrayGet($config, 'access_key_id', ''));
        $this->setAccessKeySecret(arrayGet($config, 'access_secret', ''));
        $this->setRoleArn(arrayGet($config, 'role', ''));
        $this->setOssOrigin(arrayGet($config, 'oss_origin', ''));
        $this->setUrl(arrayGet($config, 'url', $this->url));
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @param string $url
     */
    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    /**
     * @return mixed
     */
    public function getAccessKeySecret()
    {
        return $this->accessKeySecret;
    }

    /**
     * @param mixed $accessKeySecret
     */
    public function setAccessKeySecret($accessKeySecret): void
    {
        $this->accessKeySecret = $accessKeySecret;
    }

    /**
     * @return mixed
     */
    public function getAccessKeyId()
    {
        return $this->accessKeyId;
    }

    /**
     * @param mixed $accessKeyId
     */
    public function setAccessKeyId($accessKeyId): void
    {
        $this->accessKeyId = $accessKeyId;
    }

    /**
     * @return string
     */
    public function getRoleArn(): string
    {
        return $this->roleArn;
    }

    /**
     * @param string $role
     */
    public function setRoleArn(string $role): void
    {
        $this->roleArn = $role;
    }

    /**
     * @return string
     */
    public function getRoleSessionName(): string
    {
        return $this->roleSessionName;
    }

    /**
     * @param string $roleSessionName
     */
    public function setRoleSessionName(string $roleSessionName): void
    {
        $this->roleSessionName = $roleSessionName;
    }

    /**
     * @return string
     */
    public function getDurationSeconds(): string
    {
        return $this->durationSeconds;
    }

    /**
     * @param string $durationSeconds
     */
    public function setDurationSeconds(string $durationSeconds): void
    {
        $this->durationSeconds = $durationSeconds;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * @param array $config
     */
    public function setConfig(array $config): void
    {
        $this->config = $config;
    }

    /**
     * @return string
     */
    public function getBucket(): string
    {
        return $this->bucket;
    }

    /**
     * @param string $bucket
     */
    public function setBucket(string $bucket): void
    {
        $this->bucket = $bucket;
    }
}