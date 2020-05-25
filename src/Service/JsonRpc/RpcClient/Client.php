<?php

declare(strict_types=1);

namespace TheFairLib\Service\Swoole\JsonRpc\RpcClient;

use TheFairLib\Exception\Service\ServiceException;
use TheFairLib\Service\Swoole\Client\TCP;
use TheFairLib\Service\Swoole\JsonRpc\DataFormatter;
use TheFairLib\Service\Swoole\JsonRpc\JsonLengthPacker;
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
                return $response['result'];
            }

            if ($code = $response['error']['code'] ?? null) {
                $error = $response['error'];
                throw new ServiceException($error['message'] ?? '', $error['data'] ?? [], $code);
            }

            throw new ServiceException('Invalid response.');
        } catch (ServiceException $e) {
            throw new ServiceException($e->getMessage(), $e->getExtData(), $e->getCode());
        } catch (Throwable $e) {
            throw new ServiceException($e->getMessage(), $e->getTraceAsString(), $e->getCode());
        }
    }
}
