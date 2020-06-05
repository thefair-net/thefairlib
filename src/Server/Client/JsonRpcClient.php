<?php


namespace TheFairLib\Server\Client;


use Hyperf\RpcClient\AbstractServiceClient;
use TheFairLib\Constants\InfoCode;
use TheFairLib\Exception\ServiceException;
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
            if (isset($params['auth'])) {
                throw new ServiceException('auth 关键字已经被使用');
            }
            $config = $this->getConsumerConfig();
            $requestData = array_merge_recursive($params, [
                'auth' => [
                    'app_key' => $config['app_key'],
                    'app_secret' => $config['app_secret'],
                ],
            ]);
            $result = $this->__request($method, $requestData);
            if (!empty($result['code'])) {
                throw new ServiceException($result['message']['text'] ?? '', $result['result'] ?? [], $result['code']);
            }
            return $result;
        } catch (ServiceException $e) {
            throw new ServiceException($e->getMessage(), $e->getData(), $e->getCode(), $e, $e->getHttpStatus());
        } catch (Throwable $e) {
            throw new ServiceException($e->getMessage(), [], $e->getCode() == 0 ? $e->getCode() : InfoCode::CODE_ERROR);
        }
    }

}