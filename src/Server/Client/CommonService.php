<?php
/***************************************************************************
 *
 * Copyright (c) 2020 liumingzhi, Inc. All Rights Reserved
 *
 **************************************************************************
 *
 * @file Test.php
 * @author liumingzhi(liumingzhij26@gmail.com)
 * @date 2020-06-05 15:51:00
 *
 **/

namespace TheFairLib\Server\Client;

use Psr\Container\ContainerInterface;

class CommonService extends JsonRpcClient
{

    /**
     * 会有部分性能问题，每次都需要实例化
     *
     * CommonService constructor.
     * @param ContainerInterface $container
     * @param string $serviceName
     */
    public function __construct(ContainerInterface $container, string $serviceName)
    {
        $this->setServiceName($serviceName);
        parent::__construct($container);
    }

    /**
     * @param string $serviceName
     */
    public function setServiceName(string $serviceName): self
    {
        $this->serviceName = $serviceName;
        return $this;
    }

    /**
     * @param string $serviceName
     */
    public function getServiceName()
    {
        return $this->serviceName;
    }

}
