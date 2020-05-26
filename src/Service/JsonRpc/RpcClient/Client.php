<?php

declare(strict_types=1);

namespace TheFairLib\Service\JsonRpc\RpcClient;

use TheFairLib\Exception\Service\ServiceException;
use TheFairLib\Service\Swoole\Client\TCP;
use TheFairLib\Service\JsonRpc\DataFormatter;
use TheFairLib\Service\JsonRpc\JsonLengthPacker;
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
            if (isset($params['auth'])) {
                throw new ServiceException('auth 关键字已经被使用');
            }
            $requestData = array_merge_recursive($params, [
                'auth' => [
                    'app_key' => $this->_config['app_key'],
                    'app_secret' => $this->_config['app_secret'],
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
                    throw new ServiceException($result['message']['text'] ?? '', $result['result'] ?? [], $result['code']);
                }
                return $response['result'];
            }

            if ($code = $response['error']['code'] ?? null) {
                $error = $response['error'];
                throw new ServiceException($error['message'] ?? '', $error['data'] ?? [], $error['data']['code'] ?? $code);
            }

            throw new ServiceException('Invalid response.');
        } catch (ServiceException $e) {
            throw new ServiceException($e->getMessage(), $e->getExtData(), $e->getExtCode());
        } catch (Throwable $e) {
            throw new ServiceException($e->getMessage(), $e->getTraceAsString(), $e->getExtCode());
        }
    }

    protected function _getClientType()
    {
        return 'rpc';
    }
}
