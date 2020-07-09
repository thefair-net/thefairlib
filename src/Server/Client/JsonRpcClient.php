<?php


namespace TheFairLib\Server\Client;


use Hyperf\RpcClient\AbstractServiceClient;
use TheFairLib\Constants\InfoCode;
use TheFairLib\Exception\ServiceException;
use TheFairLib\Utility\Utility;
use Throwable;

abstract class JsonRpcClient extends AbstractServiceClient
{
    /**
     * 定义对应服务提供者的服务协议
     * @var string
     */
    protected $protocol = 'jsonrpc-tcp-length-check';

    public function call(string $method, array $params = [])
    {
        try {
            if (isset($params['__auth']) || isset($params['__header'])) {
                throw new ServiceException('__auth | __header 是保留关键字');
            }
            $config = $this->getConsumerConfig();
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

            $result = $this->__request($method, $requestData);
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

}