<?php

namespace TheFairLib\DB\Mysql;

use Illuminate\Container\Container;

/***************************************************************************
 *
 * Copyright (c) 2019 liumingzhi, Inc. All Rights Reserved
 *
 **************************************************************************
 *
 * @file Manager.php
 * @author liumingzhi(liumingzhij26@gmail.com)
 * @date 2019-11-27 15:02:00
 *
 **/
class Manager extends \Illuminate\Database\Capsule\Manager
{

    public function setupManager()
    {
        $factory = new ConnectionFactory($this->container);

        $this->manager = new DatabaseManager($this->container, $factory);
    }

}