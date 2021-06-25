<?php

declare(strict_types=1);

namespace TheFairLib\Service\JsonRpc\RpcClient;

use TheFairLib\Config\Config;
use TheFairLib\DB\Redis\Cache;
use TheFairLib\Exception\Service\ExceptionThrower;
use TheFairLib\Exception\Service\RetryException;
use TheFairLib\Exception\Service\ServiceException;
use TheFairLib\Service\Swoole\Client\TCP;
use TheFairLib\Service\JsonRpc\DataFormatter;
use TheFairLib\Service\JsonRpc\JsonLengthPacker;
use TheFairLib\Utility\Backoff;
use TheFairLib\Utility\Utility;
use Throwable;

class Client extends TCP
{
    /**
     * json-rpc client
     *
     * @param $url
     * @param array $params
     * @param callable|NULL $callback
     * @return mixed
     * @throws ServiceException
     */
    public function call($url, $params = [], callable $callback = NULL)
    {

        try {
            if (isset($params['__auth']) || isset($params['__header'])) {
                throw new ServiceException('__auth | __header 是保留关键字');
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
                    'real_client_ip' => Utility::getUserIp() ?? null,
                    'cid' => $_SERVER['HTTP_X_THEFAIR_CID'] ?? null,
                    'session_id' => Utility::getGpc('PHPSESSID', 'C') ?? null,
                ],
            ]);

            $dataFormatter = DataFormatter::instance();
            $data = $dataFormatter->formatRequest([
                $url,
                $requestData,
                $dataFormatter->generate(),
            ]);
            $packer = JsonLengthPacker::instance();
            $this->send($packer->pack($data));
            $response = $packer->unpack($this->recv()) ?? [];
            if (array_key_exists('result', $response)) {
                $result = $response['result'];
                switch (true) {
                    case !empty($result['code']) && $result['code'] == 500404:
                        throw new RetryException($result['message']['text'] ?? '', $result['result'] ?? [], $result['code']);
                    case !empty($result['code']):
                        throw new ServiceException($result['message']['text'] ?? '', $result['result'] ?? [], $result['code']);
                }
                return $response['result'];
            }

            if ($code = $response['error']['code'] ?? null) {
                $error = $response['error'];
                throw new ServiceException($error['message'] ?? '', $error['data'] ?? [], $error['data']['code'] ?? $code);
            }

            throw new ServiceException('Invalid response.');
        } catch (RetryException $e) {
            throw $e;
        } catch (ServiceException $e) {
            throw new ServiceException($e->getMessage(), $e->getExtData(), $e->getExtCode());
        } catch (Throwable $e) {
            throw new ServiceException($e->getMessage(), [], $e->getCode());
        }
    }

    /**
     * 智能获取数据
     *
     * @param $url
     * @param array $params
     * @param bool $showResultOnly
     * @return bool|mixed|string
     * @throws Throwable
     */
    public function smart($url, $params = [], $showResultOnly = true)
    {
        $result = $this->retry(2, function () use ($url, $params) {
            try {
                return $this->call($url, $params);
            } catch (RetryException $e) {
                throw $e;
            } catch (Throwable $e) {
                return new ExceptionThrower($e);
            }
        }, 100);

        if ($result instanceof ExceptionThrower) {
            throw $result->getThrowable();
        }
        return Utility::arrayGet($result, 'result', []);
    }

    /**
     * @param $times
     * @param callable $callback
     * @param int $sleep
     * @return mixed
     * @throws Throwable
     */
    protected function retry($times, callable $callback, int $sleep = 0)
    {
        $backoff = new Backoff($sleep);
        beginning:
        try {
            return $callback();
        } catch (\Throwable $e) {
            if (--$times < 0) {
                throw $e;
            }
            $backoff->sleep();
            goto beginning;
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
