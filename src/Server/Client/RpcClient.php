<?php
/***************************************************************************
 *
 * Copyright (c) 2021 liumingzhi, Inc. All Rights Reserved
 *
 **************************************************************************
 *
 * @file Device.php
 * @author liumingzhi(liumingzhij26@gmail.com)
 * @date 2021-01-26 10:41:00
 *
 **/

namespace TheFairLib\Server\Client;

use Hyperf\Utils\ApplicationContext;
use TheFairLib\Annotation\Doc;
use Throwable;

class RpcClient
{

    /**
     * @Doc(name="使用连接池，可以复用连接")
     *
     * @param string $serviceName
     * @return JsonRpcClient
     */
    public static function get(string $serviceName): JsonRpcClient
    {
        $serviceName = ucwords(camelize($serviceName));

        $class = sprintf('TheFairLib\Server\Client\%s', $serviceName);
        if (class_exists($class)) {
            return container($class);
        }
        return container($class);
    }

    /**
     * @Doc(name="会有部分性能问题，每次都需要实例化")
     *
     * @param string $serviceName
     * @return JsonRpcClient
     * @throws Throwable
     */
    public static function make(string $serviceName): JsonRpcClient
    {
        return retry(2, function () use ($serviceName) {//默认重试 2 次，一次 100 ms
            return make(CommonService::class, [ApplicationContext::getContainer(), $serviceName]);
        }, 100);
    }
}
