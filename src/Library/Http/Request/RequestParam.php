<?php
/***************************************************************************
 *
 * Copyright (c) 2020 liumingzhi, Inc. All Rights Reserved
 *
 **************************************************************************
 *
 * @file RequestParam.php
 * @author liumingzhi(liumingzhij26@gmail.com)
 * @date 2020-09-17 17:57:00
 *
 **/

namespace TheFairLib\Library\Http\Request;

use Hyperf\HttpServer\Router\Dispatched;
use TheFairLib\Contract\RequestParamInterface;
use Throwable;

class RequestParam extends RequestBase implements RequestParamInterface
{

    /**
     * 验证处理
     *
     * @param Dispatched $dispatched
     * @throws Throwable
     */
    public function initCoreValidation(Dispatched $dispatched)
    {
        $this->checkUrlBlacklist($dispatched);
        $this->checkValidityRouteRequest($dispatched);
        $this->autoValidateRequest($dispatched);
    }
    
}