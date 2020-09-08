<?php
/***************************************************************************
 *
 * Copyright (c) 2020 liumingzhi, Inc. All Rights Reserved
 *
 **************************************************************************
 *
 * @file ManageInterface.php
 * @author liumingzhi(liumingzhij26@gmail.com)
 * @date 2020-09-08 14:38:00
 *
 **/

namespace TheFairLib\Contract;

interface ServiceGovernanceManageInterface
{
    /**
     * 注销服务
     *
     * @return bool
     */
    public function deregisterConsul(): bool;

    /**
     * 服务注册
     */
    public function registeredServices();
}