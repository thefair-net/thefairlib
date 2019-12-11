<?php

namespace TheFairLib\DB\Mysql;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Database\Query\Processors\Processor;
use Illuminate\Support\Collection;

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
    /**
     * @param array $columns
     * @return array
     */
    public function get($columns = ['*'])
    {
        return parent::get($columns)->toArray();
    }

    /**
     * @param array $columns
     * @return Collection
     */
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

    /**
     * Execute the query and get the first result.
     *
     * @param array $columns
     * @return \Illuminate\Database\Eloquent\Model|object|static|null
     */
    public function first($columns = ['*'])
    {
        return $this->take(1)->getObject($columns)->first();
    }

    /**
     * Chunk the results of a query by comparing IDs.
     *
     * @param int $count
     * @param callable $callback
     * @param string|null $column
     * @param string|null $alias
     * @return bool
     */
    public function chunkById($count, callable $callback, $column = null, $alias = null)
    {
        $column = $column ?? $this->defaultKeyName();

        $alias = $alias ?? $column;

        $lastId = null;

        do {
            $clone = clone $this;

            // We'll execute the query for the given page and get the results. If there are
            // no results we can just break and return from here. When there are results
            // we will call the callback with the current chunk of these results here.
            $results = $clone->forPageAfterId($count, $lastId, $column)->getObject();

            $countResults = $results->count();

            if ($countResults == 0) {
                break;
            }

            // On each chunk result set, we will pass them to the callback and then let the
            // developer take care of everything within the callback, which allows us to
            // keep the memory low for spinning through large result sets for working.
            if ($callback($results->toArray()) === false) {
                return false;
            }

            $lastId = $results->last()->{$alias};

            unset($results);
        } while ($countResults == $count);

        return true;
    }

    /**
     * Chunk the results of the query.
     *
     * @param int $count
     * @param callable $callback
     * @return bool
     */
    public function chunk($count, callable $callback)
    {
        $this->enforceOrderBy();

        $page = 1;

        do {
            // We'll execute the query for the given page and get the results. If there are
            // no results we can just break and return from here. When there are results
            // we will call the callback with the current chunk of these results here.
            $results = $this->forPage($page, $count)->getObject();

            $countResults = $results->count();

            if ($countResults == 0) {
                break;
            }

            // On each chunk result set, we will pass them to the callback and then let the
            // developer take care of everything within the callback, which allows us to
            // keep the memory low for spinning through large result sets for working.
            if ($callback($results->toArray(), $page) === false) {
                return false;
            }

            unset($results);

            $page++;
        } while ($countResults == $count);

        return true;
    }
}