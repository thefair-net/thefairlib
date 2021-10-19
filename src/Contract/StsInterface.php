<?php
/***************************************************************************
 *
 * Copyright (c) 2021 liumingzhi, Inc. All Rights Reserved
 *
 **************************************************************************
 *
 * @file StsInterface.php
 * @author liumingzhi(liumingzhij26@gmail.com)
 * @date 2021-10-19 16:46:00
 *
 **/

namespace TheFairLib\Contract;

use GuzzleHttp\Exception\GuzzleException;

interface StsInterface
{
    /**
     *
     * @param string $bucket
     * @return array
     * @throws GuzzleException
     */
    public function sts(string $bucket): array;
}