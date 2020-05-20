<?php
/***************************************************************************
 *
 * Copyright (c) 2020 liumingzhi, Inc. All Rights Reserved
 *
 **************************************************************************
 *
 * @file DbQuery.php
 * @author liumingzhi(liumingzhij26@gmail.com)
 * @date 2020-03-23 12:44:00
 *
 **/

namespace TheFairLib\Server\Security;

use TheFairLib\Library\Logger\Logger;
use Hyperf\Utils\Context;

class DbQuery
{
    public function setStatus($name)
    {
        Context::set(__CLASS__ . ':status:' . $name, true);
    }

    public function getStatus($name)
    {
        return Context::get(__CLASS__ . ':status:' . $name);
    }

    /**
     * 不允许写原生 sql，系统会监控所有的 sql 语句
     *
     * @param string $query
     * @param array $bindings
     * @param string $connectionName
     */
    public function checkQuery(string $query, array $bindings, string $connectionName): void
    {
        $sql = trim($query);
        $name = md5($sql);
        if ($this->getStatus($name)) {//同一个请求中，如果已经验证一次之后，就跳过
            return;
        }
        if (empty($bindings) && preg_match('/^(select|delete|update)/i', $sql)) {
            Logger::get()->warning(sprintf('【系统预警】风险 sql: %s, connection: %s,', $sql, $connectionName));
            $this->setStatus($name);
        }
    }
}
