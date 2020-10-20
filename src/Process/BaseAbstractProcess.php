<?php

namespace TheFairLib\Process;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Process\AbstractProcess;
use Hyperf\Utils\Context;
use Psr\Container\ContainerInterface;

/***************************************************************************
 *
 * Copyright (c) 2020 liumingzhi, Inc. All Rights Reserved
 *
 **************************************************************************
 *
 * @file BaseAbstractProcess.php
 * @author liumingzhi(liumingzhij26@gmail.com)
 * @date 2020-05-16 05:09:00
 *
 **/
abstract class BaseAbstractProcess extends AbstractProcess
{

    /**
     * 是否开启进程
     *
     * @var bool
     */
    protected $enable = true;

    public function __construct(ContainerInterface $container, ConfigInterface $config)
    {
        parent::__construct($container);
        $param = $config->get('crontab.task.' . static::class);
        $this->nums = $param['nums'] ?? 1;
        $this->enableCoroutine = $param['enable_coroutine'] ?? true;
        $this->setEnable($param['enable'] ?? true);
    }

    public function setEnable(bool $enable)
    {
        Context::set(static::class . ':enable', $enable);
    }

    public function isEnable($server): bool
    {
        return (bool)Context::get(static::class . ':enable');
    }
}
