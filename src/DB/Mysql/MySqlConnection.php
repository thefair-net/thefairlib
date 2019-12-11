<?php
/***************************************************************************
 *
 * Copyright (c) 2019 liumingzhi, Inc. All Rights Reserved
 *
 **************************************************************************
 *
 * @file MySqlConnection.php
 * @author liumingzhi(liumingzhij26@gmail.com)
 * @date 2019-11-27 16:31:00
 *
 **/

namespace TheFairLib\DB\Mysql;

use Illuminate\Database\Query\Builder;

class MySqlConnection extends \Illuminate\Database\MySqlConnection
{
    /**
     * @return Builder|QueryBuilder
     */
    public function query()
    {
        return new QueryBuilder(
            $this, $this->getQueryGrammar(), $this->getPostProcessor()
        );
    }

}