<?php


namespace TheFairLib\Server\Client;

use Hyperf\RpcClient\AbstractServiceClient;
use Hyperf\Utils\Context;
use InvalidArgumentException;
use TheFairLib\Constants\InfoCode;
use TheFairLib\Exception\ServiceException;
use TheFairLib\Library\Cache\Redis;
use Throwable;

abstract class JsonRpcClient extends AbstractServiceClient
{

    /**
     * 最大缓存时间
     *
     * @var int
     */
    const TTL_MAX = 1800;

    /**
     * 定义对应服务提供者的服务协议
     * @var string
     */
    protected $protocol = 'jsonrpc-tcp-length-check';

    protected function getServicePath()
    {
        if (!$this->serviceName) {
            return $this->serviceName;
        }
        return Context::get(__CLASS__ . '::servicePath');
    }

    protected function setServicePath(string $path)
    {
        if (in_array($path, ['.', '..'])) {
            return;
        }
        Context::set(__CLASS__ . '::servicePath', $path);
    }

    protected function __generateRpcPath(string $methodName): string
    {
        if (!$this->serviceName || !$this->getServicePath()) {
            throw new InvalidArgumentException('Parameter $serviceName missing.');
        }
        return $this->pathGenerator->generate($this->getServicePath(), $methodName);
    }

    public function call(string $method, array $params = [])
    {
        try {
            if (isset($params['__auth']) || isset($params['__header'])) {
                throw new ServiceException('__auth | __header 是保留关键字');
            }
            $config = $this->getConsumerConfig();
            if (empty($config)) {
                throw new ServiceException('error config');
            }
            $time = time();
            $sign = md5(sprintf('%s%s%d', $config['app_key'], $config['app_secret'], $time));
            $requestData = array_merge_recursive($params, [
                '__auth' => [
                    'app_key' => $config['app_key'],
                    'sign' => $sign,
                    'time' => $time,
                ],
                '__header' => [
                    'client_ip' => getServerLocalIp(),
                ],
            ]);
            $result = $this->__request($this->generate($method), $requestData);
            if (!empty($result['code'])) {
                throw new ServiceException($result['message']['text'] ?? '', $result['result'] ?? [], (int)$result['code']);
            }
            return $result;
        } catch (ServiceException $e) {
            throw new ServiceException($e->getMessage(), $e->getData(), (int)$e->getCode(), $e, $e->getHttpStatus());
        } catch (Throwable $e) {
            throw new ServiceException($e->getMessage(), [], (int)$e->getCode() > 0 ? (int)$e->getCode() : InfoCode::CODE_ERROR);
        }
    }

    /**
     * 返回数组
     *
     * @param string $method
     * @param array $params
     * @param int $ttl
     * @param string $poolName
     * @return array
     */
    public function smart(string $method, array $params = [], int $ttl = 0, string $poolName = 'default'): array
    {
        switch (true) {
            case $ttl > 0 && !config('app.close_rpc_smart_cache', false):
                $key = $this->getCacheKey($method, $params);
                if (!($result = $this->getCache($key, $poolName))) {
                    $result = $this->call($method, $params);
                    $this->setCache($key, $result, $ttl, $poolName);
                }
                break;
            default:
                $result = $this->call($method, $params);
                break;
        }
        //@todo
        return arrayGet($result, 'result', []);
    }


    /**
     * 清除由 smart 生成的缓存
     *
     * @param string $method
     * @param array $params
     * @param string $poolName
     */
    public function clear(string $method, array $params = [], string $poolName = 'default'): void
    {
        Redis::getContainer($poolName)->del($this->getCacheKey($method, $params));
    }

    /**
     * 生成 path
     *
     * @param string $method
     * @return string
     */
    protected function generate(string $method)
    {
        $handledNamespace = trim($method, '/');
        $this->setServicePath(dirname($handledNamespace));
        return basename($handledNamespace);
    }

    /**
     * 缓存处理
     *
     * @param string $id
     * @param string $poolName
     * @return array
     */
    protected function getCache(string $id, string $poolName = 'default')
    {
        $data = [];
        if (Redis::getContainer($poolName)->exists($id)) {
            $data = Redis::getContainer($poolName)->get($id);
        }
        return !empty($data) ? decode($data) : [];
    }

    /**
     * 缓存
     *
     * @param string $id
     * @param array $data
     * @param $ttl
     * @param $poolName
     */
    protected function setCache(string $id, array $data, $ttl, $poolName)
    {
        if ($ttl > 0) {
            $ttl = min($ttl, self::TTL_MAX);
            $str = encode($data);
            if (strlen($str) <= (10 * 1024)) {//超过 10k 不缓存
                Redis::getContainer($poolName)->setex($id, $ttl, $str);
            }
        }
    }

    /**
     * 缓存 key
     *
     * @param string $method
     * @param array $params
     * @return string
     */
    protected function getCacheKey(string $method, array $params = []): string
    {
        $id = md5(encode([$method, $params]));
        return getPrefix('Cache', 'string') . sprintf('%s#%s', env('APP_NAME'), $id);
    }
}
