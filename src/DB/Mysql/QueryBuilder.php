<?php

namespace TheFairLib\DB\Mysql;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Database\Query\Processors\Processor;

/***************************************************************************
 *
 * Copyright (c) 2019 liumingzhi, Inc. All Rights Reserved
 *
 **************************************************************************
 *
 * @file QueryBuilder.php
 * @author liumingzhi(liumingzhij26@gmail.com)
 * @date 2019-11-27 15:00:00
 *
 **/
class QueryBuilder extends \Illuminate\Database\Query\Builder
{
    public function get($columns = ['*'])
    {
        return parent::get($columns)->toArray();
    }

}