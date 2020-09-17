<?php
/***************************************************************************
 *
 * Copyright (c) 2020 liumingzhi, Inc. All Rights Reserved
 *
 **************************************************************************
 *
 * @file RequestParamInterface.php
 * @author liumingzhi(liumingzhij26@gmail.com)
 * @date 2020-09-17 18:05:00
 *
 **/

namespace TheFairLib\Contract;

use Hyperf\HttpServer\Router\Dispatched;

interface RequestParamInterface
{
    /**
     * 验证处理
     *
     * @param Dispatched $dispatched
     */
    public function initCoreValidation(Dispatched $dispatched);

    /**
     * 判断是否实现了验证类
     *
     * @param string $classname
     * @return bool
     * @see ValidationMiddleware::isImplementedValidatesWhenResolved()
     */
    public function isImplementedValidatesWhenResolved(string $classname): bool;
}