<?php
/***************************************************************************
 *
 * Copyright (c) 2021 liumingzhi, Inc. All Rights Reserved
 *
 **************************************************************************
 *
 * @file FileFactory.php
 * @author liumingzhi(liumingzhij26@gmail.com)
 * @date 2021-06-01 14:16:00
 *
 **/

namespace TheFairLib\Library\File;

use Hyperf\Redis\RedisFactory;
use Hyperf\Utils\ApplicationContext;
use TheFairLib\Contract\FileInterface;

class FileFactory
{

    /**
     * file 对象
     *
     * @return FileInterface
     */
    public static function get()
    {
        return ApplicationContext::getContainer()->get(PublicFile::class);
    }
}
