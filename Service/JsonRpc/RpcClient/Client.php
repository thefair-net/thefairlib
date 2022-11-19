<?php

namespace TheFairLib\Service\JsonRpc\RpcClient;

use Exception;
use TheFairLib\Config\Config;
use TheFairLib\DB\Redis\Cache;
use TheFairLib\Exception\Api\ApiException;
use TheFairLib\Service\Swoole\Client\TCP;
use TheFairLib\Service\JsonRpc\DataFormatter;
use TheFairLib\Service\JsonRpc\JsonLengthPacker;
use TheFairLib\Utility\Utility;

class Client extends TCP
{
    /**
     * json-rpc client
     *
     * @param $url
     * @param array $params
     * @param callable|NULL $callback
     * @return mixed
     * @throws Exception
     */
    public function call($url, $params = [], callable $callback = NULL)
    {

        try {
            if (isset($params['__auth']) || isset($params['__header'])) {
                throw new Exception('__auth | __header 是保留关键字');
            }
            $time = time();
            $sign = md5(sprintf('%s%s%d', $this->_config['app_key'], $this->_config['app_secret'], $time));
            $requestData = array_merge_recursive($params, [
                '__auth' => [
                    'app_key' => $this->_config['app_key'],
                    'sign' => $sign,
                    'time' => $time,
                ],
                '__header' => [
                    'real_client_ip' => Utility::getUserIp(),
                    'cid' => !empty($_SERVER['HTTP_X_THEFAIR_CID']) ? $_SERVER['HTTP_X_THEFAIR_CID'] : null,
                    'session_id' => Utility::getGpc('PHPSESSID', 'C'),
                ],
            ]);

            $dataFormatter = DataFormatter::instance();
            $data = $dataFormatter->formatRequest([
                $url,
                $requestData,
                $dataFormatter->generate(),
            ]);
            $packer = JsonLengthPacker::instance();
            $response = $packer->unpack((string)$this->send($packer->pack($data)));
            if (array_key_exists('result', $response)) {
                $result = $response['result'];
                if (!empty($result['code'])) {
                    throw new Exception($result['message']['text']);
                }
                return $response['result'];
            }

            if ($code = $response['error']['code']) {
                $error = $response['error'];
                throw new Exception($error['message']);
            }

            throw new Exception('Invalid response.');
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * 智能获取数据
     *
     * @param $url
     * @param array $params
     * @param bool $showResultOnly
     * @return bool|mixed|string
     * @throws Exception
     */
    public function smart($url, $params = [], $showResultOnly = true)
    {
        $cacheTtl = $this->_getServiceCacheTtl($url);
        //获取缓存的key
        $cacheKey = $this->_getServiceCacheKey($url, $params);
        if ($cacheTtl !== false) {
            $result = $this->_getCache()->get($cacheKey);
        }

        if (empty($result)) {
            $result = $this->call($url, $params);
            if ($cacheTtl !== false) {
                $this->_getCache()->setex($cacheKey, $cacheTtl, Utility::encode($result));
            }
        } else {
            $result = Utility::decode($result);
        }

        //如果设置了只返回结果,当code!=0的时候,直接抛出异常
        if ($showResultOnly === true) {
            if (!empty($result['code'])) {
                throw new Exception($result['message'], $result['code']);
            } else {
                return $result['result'];
            }
        } else {
            return $result;
        }
    }

    protected function _getClientType()
    {
        return 'rpc';
    }

    private function _getServiceCacheTtl($url)
    {
        $key = $this->_getClientType() . '.' . $this->getServerTag() . '.' . str_replace('/', '_', substr($url, 1));
        $ttl = Config::get_service_cache($key);
        return empty($ttl) ? false : $ttl;
    }

    private function _getServiceCacheKey($url, $params)
    {
        $serviceConfig = $this->_getServiceConfigKey($url);
        return !empty($serviceConfig) ? 'service_cache_' . Config::get_app('phase') . '::' . $serviceConfig . '_' . md5($this->getServerTag() . $url . Utility::encode($params)) : null;
    }

    private function _getServiceConfigKey($url)
    {
        return strtolower($this->getServerTag() . '::' . str_replace('/', '_', substr($url, 1)));
    }

    private function _getCache()
    {
        $serviceConf = $this->_getServiceConfig($this->getServerTag());
        $cacheNode = !empty($serviceConf['cache_node']) ? $serviceConf['cache_node'] : 'default';
        return Cache::getInstance($cacheNode);
    }
}
