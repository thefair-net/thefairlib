<?php

namespace TheFairLib\Aliyun\AliOSS;

use TheFairLib\Config\Config;

class OssClient
{

    static public $instance;

    static public $bucket;

    static public $config;

    /**
     * @param $bucket
     * @return \OSS\OssClient
     */
    static public function Instance($bucket)
    {
        if (empty(self::$instance[$bucket])) {
            $config = Config::get_api_ali_oss("{$bucket}");//固定的文件目录
            self::$instance[$bucket] = new \OSS\OssClient($config['app_key'], $config['app_secret'], $config['endpoint']);
            self::$bucket = $bucket;
            self::$config = $config;
        }
        return self::$instance[$bucket];
    }

}
