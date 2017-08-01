<?php

namespace TheFairLib\Aliyun\AliOSS;

use OSS\Core\OssException;
use TheFairLib\Config\Config;

class OssClient
{

    static public $instance;

    static public $bucket;

    static public $config;

    /**
     * @param $bucket
     * @return OssClient
     */
    static public function Instance($bucket)
    {
        $class = get_called_class();
        if (empty(self::$instance[$class])) {
            $config = Config::get_api_ali_oss("{$bucket}");//固定的文件目录
            self::$bucket = $bucket;
            self::$config = $config;
            self::$instance[$class] = new $class();
        }
        return self::$instance[$class];
    }

    /**
     * @return \OSS\OssClient
     */
    public function ossClient()
    {
        if (empty(self::$instance[self::$bucket])) {
            self::$instance[self::$bucket] = new \OSS\OssClient(self::$config['app_key'], self::$config['app_secret'], self::$config['endpoint']);
        }
        return self::$instance[self::$bucket];
    }

    public function signObject($url, $timeout = 60, $method = 'GET', $options = NULL)
    {
        if (self::$config['acl_type'] == 'private') {//私有读
            try {
                $info = parse_url($url);
                $object = ltrim($info['path'], '/');
                $signUrl = $this->ossClient()->signUrl(self::$bucket, $object, $timeout, $method, $options);
                $result = parse_url($signUrl);
                $url = $info['scheme'] . '://' . $info['host'] . $info['path'] . '?' . $result['query'];
            } catch (\Exception $e) {
                throw new OssException('signUrl error ' . $e->getMessage());
            }
        }
        return $url;
    }

    public function signCdn($url, $timeout = 1800)
    {
        $timeout = time() - 1800 + $timeout;//阿里云默认时间1800秒
        if (self::$config['acl_type'] == 'private') {//私有读
            try {
                $info = parse_url($url);
                $date = date('YmdHi', $timeout);
                $sign = md5(self::$config['cdn_sign_key'] . $date . $info['path']);
                $url = $info['scheme'] . '://' . $info['host'] . '/' . $date . '/' . $sign . $info['path'];
            } catch (\Exception $e) {
                throw new OssException('signCdn error ' . $e->getMessage());
            }
        }
        return $url;
    }

}
