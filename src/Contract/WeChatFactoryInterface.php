<?php
/***************************************************************************
 *
 * Copyright (c) 2021 liumingzhi, Inc. All Rights Reserved
 *
 **************************************************************************
 *
 * @file WeChatFactoryInterface.php
 * @author liumingzhi(liumingzhij26@gmail.com)
 * @date 2021-02-23 16:47:00
 *
 **/

namespace TheFairLib\Contract;

use EasyWeChat\OfficialAccount\Application;

interface WeChatFactoryInterface
{
    /**
     * 实例
     *
     * @param string $type
     * @param string $appLabel
     * @param string $category
     * @return Application|\EasyWeChat\MiniProgram\Application|\EasyWeChat\OpenPlatform\Application
     */
    public function getApp(string $type, string $appLabel, string $category = '');
}
