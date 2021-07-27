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

class FileFactory
{

    /**
     * file 对象
     *
     * @param string $bucket
     * @return PublicFile
     */
    public static function get(string $bucket = ''): PublicFile
    {
        return make(PublicFile::class, [$bucket]);
    }
}
