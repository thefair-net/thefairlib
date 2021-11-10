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

use GuzzleHttp\Exception\GuzzleException;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Filesystem\FilesystemFactory;
use Hyperf\Guzzle\ClientFactory;
use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\FilesystemException;
use OSS\OssClient;
use Overtrue\Flysystem\Qiniu\QiniuAdapter;
use Qiniu\Storage\BucketManager;
use TheFairLib\Exception\ServiceException;
use TheFairLib\Library\Logger\Logger;
use Throwable;

class PublicFile
{
    const DEFAULT_IMAGE_TYPE = 'png';

    /**
     * @Inject
     * @var FilesystemFactory
     */
    public $factory;

    /**
     * @var string
     */
    protected $bucket;

    /**
     * @Inject()
     * @var ClientFactory
     */
    protected $clientFactory;

    /**
     * PublicFile constructor.
     * @param string $bucket
     */
    public function __construct(string $bucket)
    {
        $this->bucket = $bucket;
    }

    /**
     * 重命名
     *
     * @param string $name
     * @param string $newName
     * @return bool
     * @throws FilesystemException|Throwable
     */
    public function rename(string $name, string $newName): bool
    {
        try {
            $file = $this->filesystem($this->bucket);
            if (!$file->fileExists($name) || !$file->fileExists($newName)) {
                throw new ServiceException('文件不存在: ' . $name . ' ' . $newName);
            }
            $file->move($name, $newName);
            return true;
        } catch (Throwable $e) {
            throw  $e;
        }
    }

    /**
     * 调用CopyObject接口拷贝同一地域下相同或不同存储空间（Bucket）之间的文件（Object）
     *
     * @param string $name
     * @param string $newName
     * @param string $newBucket
     * @return bool
     * @throws Throwable
     */
    public function copy(string $name, string $newName, string $newBucket): bool
    {
        $file = $this->filesystem($this->bucket);
        if (!$file->fileExists($name)) {
            throw new ServiceException('文件不存在: ' . $name);
        }
        try {
            /**
             * @var BucketManager|OssClient
             */
            $client = $this->getAdapter()->getClient();
            switch (get_class($client)) {
                case OssClient::class:
                    $data = $client->copyObject($this->bucket, $name, $newBucket, $newName);
                    break;
                case BucketManager::class:
                    $client->copy($this->bucket, $name, $newBucket, $newName);
                    $data = ['status' => true];
                    break;
                default:
                    throw new ServiceException('目前只支持阿里云与七牛云', [
                        'client' => get_class($client),
                        'name' => $name,
                    ]);
            }
            return !empty($data);
        } catch (Throwable $e) {
            throw $e;
        }
    }

    /**
     * 对象
     *
     * @param string $name
     * @return Filesystem
     */
    public function filesystem(string $name = ''): Filesystem
    {
        return $this->factory->get($name ?: $this->bucket);
    }

    /**
     * @return FilesystemAdapter
     */
    protected function getAdapter(): FilesystemAdapter
    {
        /**
         * @var OssClient|BucketManager $client
         */
        $options = config('file', []);
        return $this->factory->getAdapter($options, $this->bucket);
    }

    /**
     * 上传文件到阿里云
     *
     * @param string $base64
     * @param string $path
     * @param string $ossSaveFilename
     * @return array
     * @throws Throwable
     */
    public function uploadImage(string $base64, string $path = 'public', string $ossSaveFilename = ''): array
    {
        $contents = base64_decode($base64);
        if (!$contents) {
            throw new ServiceException('base64 解码错误');
        }
        $filename = $this->getFilenamePath($path, $ossSaveFilename ?: 'base64.' . self::DEFAULT_IMAGE_TYPE);
        return $this->upload($filename, $contents);
    }

    /**
     * 上传文件
     *
     * @param string $filename
     * @param $content
     * @param string $path
     * @return array
     * @throws Throwable
     */
    public function fileUpload(string $filename, $content, string $path = 'public'): array
    {
        $filename = $this->getFilenamePath($path, $filename);

        return $this->upload($filename, $content);
    }

    /**
     * 上传文件
     *
     * @param string $filename
     * @param string $ossSaveFilename
     * @param string $path
     * @return array
     * @throws Throwable
     */
    public function filePathUpload(string $filename, string $ossSaveFilename, string $path = 'public'): array
    {
        if (!file_exists($filename)) {
            throw new ServiceException('文件不存在：' . $filename);
        }
        $content = file_get_contents($filename);
        if (!$content) {
            throw new ServiceException('文件内容不能为空');
        }
        $filename = $this->getFilenamePath($path, $ossSaveFilename);

        return $this->upload($filename, $content);
    }

    /**
     * 上传文件
     *
     * @param $filename
     * @param $contents
     * @return array
     * @throws Throwable
     */
    private function upload($filename, $contents): array
    {
        try {
            $file = $this->filesystem($this->bucket);
            $file->write($filename, $contents);//写入文件
            /**
             * @var BucketManager|OssClient
             */
            $client = $this->getAdapter()->getClient();
            $config = config(sprintf('file.storage.%s', $this->bucket), []);
            switch (get_class($client)) {
                case OssClient::class:
                    $url = rtrim(arrayGet($config, 'origin', '/')) . '/' . $filename;
                    break;
                case BucketManager::class:
                    $url = rtrim(arrayGet($config, 'domain', '/')) . '/' . $filename;
                    break;
                default:
                    throw new ServiceException('目前只支持阿里云与七牛云', [
                        'client' => get_class($client),
                        'name' => $filename,
                    ]);
            }
        } catch (Throwable $e) {
            throw  $e;
        }
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
    private function getOssFolder($pathStr): string
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
    private function getFilenamePath($pathStr, string $filename): string
    {
        if (empty($pathStr)) {
            return $filename;
        }
        return $this->getOssFolder($pathStr) . '/' . md5(microtime(true) . mt_rand(1, 1000000)) . $this->getType($filename);
    }

    private function getType(string $filename): string
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
     * @throws FilesystemException|Throwable
     */
    public function fileLocal(string $filename, $content): array
    {
        try {
            $file = $this->filesystem($this->bucket);
            if ($file->fileExists($filename)) {
                $file->delete($filename);
            }
            $file->write($filename, $content);
        } catch (Throwable $e) {
            throw $e;
        }
        return [
            'filepath' => $filename,
            'size' => strlen($content),
        ];
    }

    /**
     * 删除文件
     *
     * @param string $filename
     * @return bool
     */
    public function deleteFile(string $filename): bool
    {
        try {
            $file = $this->filesystem($this->bucket);
            if (!$file->fileExists($filename)) {
                return true;
            }
            $file->delete($filename);
            return true;
        } catch (Throwable $e) {
            throw new ServiceException('删除文件失败 .' . $e->getMessage(), [
                'error' => formatter($e),
            ]);
        }
    }

    /**
     * url 上传
     *
     * @param string $url
     * @param string $path
     * @param string $ossSaveFilename
     * @param array $header
     * @return array
     * @throws GuzzleException
     * @throws Throwable
     */
    public function urlUpload(string $url, string $path = 'public', string $ossSaveFilename = '', array $header = []): array
    {
        try {
            $client = $this->clientFactory->create([
                'timeout' => 60.0,//有可能大文件
            ]);
            if (empty($header)) {
                $header = [
                    'referer' => $url,
                    'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.114 Safari/537.36',
                    'accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
                ];
            }
            $response = $client->get($url, [
                'headers' => $header,
            ]);
            $content = $response->getBody()->getContents();

            if ($response->getStatusCode() != 200 || empty($content)) {
                throw new ServiceException('url_upload:error', [
                    'url' => $url,
                    'error' => $response->getStatusCode(),
                    'content' => $response->getBody()->getSize(),
                ]);
            }
            return $this->uploadImage(base64_encode($content), $path, $ossSaveFilename);
        } catch (Throwable $e) {
            throw $e;
        }
    }

    /**
     * 私有库
     *
     * @param string $path
     * @param int $expires
     * @return string
     * @throws Throwable
     */
    public function privateDownloadUrl(string $path, int $expires = 3600): string
    {
        $url = '';
        try {
            /**
             * @var OssClient|BucketManager $client
             */
            $client = $this->getAdapter()->getClient();
            switch (get_class($client)) {
                case OssClient::class:
                    break;
                case BucketManager::class:
                    /**
                     * @var QiniuAdapter $adapter
                     */
                    $adapter = $this->getAdapter();
                    $url = $adapter->privateDownloadUrl($path, $expires);
                    break;
                default:
                    throw new ServiceException('目前只支持阿里云与七牛云', [
                        'client' => get_class($client),
                        'name' => $this->bucket,
                    ]);
            }
            return $url;
        } catch (Throwable $e) {
            throw $e;
        }
    }

    /**
     * 签名
     *
     * @param null $key
     * @param int $expires
     * @param null $policy
     * @param null $strictPolice
     * @return array
     * @throws Throwable
     */
    public function getUploadToken($key = null, int $expires = 3600, $policy = null, $strictPolice = null): array
    {
        try {
            /**
             * @var BucketManager $client
             */
            $client = $this->getAdapter()->getClient();
            switch (get_class($client)) {
                case BucketManager::class:
                    $token = $this->getAdapter()->getUploadToken($key, $expires, $policy, $strictPolice);
                    $data = [
                        'token' => $token,
                    ];
                    break;
                default:
                    throw new ServiceException('目前只支持七牛云', [
                        'client' => get_class($client),
                        'name' => $this->bucket,
                    ]);
            }
            Logger::get()->info('get_upload_token', [
                'ret' => $data,
                'bucket' => $this->bucket,
            ]);
            return $data;
        } catch (Throwable $e) {
            throw $e;
        }
    }
}
