<?php
/***************************************************************************
 *
 * Copyright (c) 2020 liumingzhi, Inc. All Rights Reserved
 *
 **************************************************************************
 *
 * @file FileService.php
 * @author liumingzhi(liumingzhij26@gmail.com)
 * @date 2020-12-13 11:36:00
 *
 **/

namespace TheFairLib\Library\File;

use Hyperf\Filesystem\FilesystemFactory;
use League\Flysystem\FileExistsException;
use League\Flysystem\FileNotFoundException;
use TheFairLib\Contract\FileInterface;
use TheFairLib\Exception\ServiceException;

class PublicFile implements FileInterface
{
    const DEFAULT_IMAGE_TYPE = 'png';

    /**
     * @var FilesystemFactory
     */
    public $factory;

    public function __construct(FilesystemFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * 上传文件到阿里云
     *
     * @param string $base64
     * @param string $path
     * @return array
     * @throws FileExistsException
     */
    public function uploadImage(string $base64, $path = 'public')
    {
        $contents = base64_decode($base64);
        if (!$contents) {
            throw new ServiceException('base64 解码错误');
        }
        $filename = $this->getFilenamePath($path, 'base64.' . self::DEFAULT_IMAGE_TYPE);
        return $this->upload($filename, $contents);
    }

    /**
     * 上传文件
     *
     * @param string $filename
     * @param $content
     * @param string $path
     * @return array
     * @throws FileExistsException
     */
    public function fileUpload(string $filename, $content, $path = 'public'): array
    {
        $filename = $this->getFilenamePath($path, $filename);

        return $this->upload($filename, $content);
    }

    /**
     * 上传文件
     *
     * @param $filename
     * @param $contents
     * @return array
     * @throws FileExistsException
     */
    private function upload($filename, $contents)
    {
        $file = $this->factory->get('oss');
        if (!$file->write($filename, $contents)) {
            throw new ServiceException('上传文件失败');
        }
        $url = $file->getConfig()->get('origin', '/') . $filename;
        return [
            'url' => $url,
            'size' => strlen($contents),
        ];
    }

    /**
     * 按照日期自动创建存储文件夹
     *
     * @param $pathStr
     * @return string
     */
    private function getOssFolder($pathStr)
    {
        if (strrchr($pathStr, "/") != "/") {
            $pathStr .= "/";
        }
        $pathStr .= date("Ymd");
        return $pathStr;
    }

    /**
     * 重命名文件
     * @param $pathStr
     * @param string $filename
     * @return string
     */
    private function getFilenamePath($pathStr, string $filename)
    {
        return $this->getOssFolder($pathStr) . '/' . md5(strval(microtime(true)) . mt_rand(1, 1000000)) . $this->getType($filename);
    }

    private function getType(string $filename)
    {
        $basename = basename($filename);
        $type = arrayGet(pathinfo($basename), 'extension');
        if (empty($type)) {
            throw new ServiceException('必须有文件扩展名: ' . $filename);
        }
        return sprintf('.%s', $type);
    }

    /**
     * 本地上传
     *
     * @param string $filename
     * @param $content
     * @return array
     * @throws FileExistsException
     * @throws FileNotFoundException
     */
    public function fileLocal(string $filename, $content): array
    {
        $file = $this->factory->get('local');
        if ($file->has($filename)) {
            $file->delete($filename);
        }
        if (!$file->write($filename, $content)) {
            throw new ServiceException('上传文件失败');
        }
        return [
            'filepath' => $filename,
            'size' => strlen($content),
        ];
    }
}
