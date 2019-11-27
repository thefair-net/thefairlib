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

    public function getObject($columns = ['*'])
    {
        return parent::get($columns);
    }

    /**
     * Execute an aggregate function on the database.
     *
     * @param string $function
     * @param array $columns
     * @return mixed
     */
    public function aggregate($function, $columns = ['*'])
    {
        $results = $this->cloneWithout($this->unions ? [] : ['columns'])
            ->cloneWithoutBindings($this->unions ? [] : ['select'])
            ->setAggregate($function, $columns)
            ->getObject($columns)->toBase();

        if (!$results->isEmpty()) {
            return array_change_key_case((array)$results[0])['aggregate'];
        }
    }

}