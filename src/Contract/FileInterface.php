<?php
/***************************************************************************
 *
 * Copyright (c) 2020 liumingzhi, Inc. All Rights Reserved
 *
 **************************************************************************
 *
 * @file FileInterface.php
 * @author liumingzhi(liumingzhij26@gmail.com)
 * @date 2021-06-01 12:13:00
 *
 **/

namespace TheFairLib\Contract;

use League\Flysystem\FileExistsException;

interface FileInterface
{
    /**
     * 上传文件到阿里云
     *
     * @param string $base64
     * @param string $path
     * @return array
     * @throws FileExistsException
     */
    public function uploadImage(string $base64, $path = 'public');

    /**
     * 上传文件
     *
     * @param string $filename
     * @param $content
     * @param string $path
     * @return array
     */
    public function fileUpload(string $filename, $content, $path = 'public'): array;

    /**
     * 本地上传
     *
     * @param string $filename
     * @param $content
     * @return array
     * @throws FileExistsException
     */
    public function fileLocal(string $filename, $content): array;
}
